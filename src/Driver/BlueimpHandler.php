<?php

namespace CodingSocks\UploadHandler\Driver;

use Closure;
use CodingSocks\UploadHandler\Helper\ChunkHelpers;
use CodingSocks\UploadHandler\Identifier\Identifier;
use CodingSocks\UploadHandler\Range\ContentRange;
use CodingSocks\UploadHandler\Response\PercentageJsonResponse;
use CodingSocks\UploadHandler\StorageConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class BlueimpHandler extends BaseHandler
{
    use ChunkHelpers;

    /**
     * @var string
     */
    private $fileParam;

    /**
     * @var \CodingSocks\UploadHandler\Identifier\Identifier
     */
    private $identifier;

    /**
     * BlueimpDriver constructor.
     *
     * @param array $config
     * @param \CodingSocks\UploadHandler\Identifier\Identifier $identifier
     */
    public function __construct($config, Identifier $identifier)
    {
        $this->fileParam = $config['param'];
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        if ($this->isRequestMethodIn($request, [Request::METHOD_HEAD, Request::METHOD_OPTIONS])) {
            return $this->info();
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_GET])) {
            return $this->download($request, $config);
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH])) {
            return $this->save($request, $config, $fileUploaded);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_HEAD,
            Request::METHOD_OPTIONS,
            Request::METHOD_GET,
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_PATCH,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function info(): Response
    {
        return new JsonResponse([], Response::HTTP_OK, [
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Content-Disposition' => 'inline; filename="files.json"',
            'X-Content-Type-Options' => 'nosniff',
            'Vary' => 'Accept',
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \CodingSocks\UploadHandler\StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Request $request, StorageConfig $config): Response
    {
        $request->validate([
            $this->fileParam => 'required',
            'totalSize' => 'required',
        ]);

        $originalFilename = $request->query($this->fileParam);
        $totalSize = $request->query('totalSize');
        $uid = $this->identifier->generateFileIdentifier($totalSize, $originalFilename);

        if (!$this->chunkExists($config, $uid)) {
            return new JsonResponse([
                'file' => null,
            ]);
        }

        $chunk = Arr::last($this->chunks($config, $uid));
        $size = explode('-', basename($chunk))[1] + 1;

        return new JsonResponse([
            'file' => [
                'name' => $originalFilename,
                'size' => $size,
            ],
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \CodingSocks\UploadHandler\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        $file = $request->file($this->fileParam);

        if (null === $file) {
            $file = Arr::first($request->file(Str::plural($this->fileParam), []));
        }

        $this->validateUploadedFile($file);

        try {
            $range = new ContentRange($request->headers);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $uid = $this->identifier->generateFileIdentifier($range->getTotal(), $file->getClientOriginalName());

        $chunks = $this->storeChunk($config, $range, $file, $uid);

        if (!$range->isLast()) {
            return new PercentageJsonResponse($range->getPercentage());
        }

        $targetFilename = $file->hashName();

        $path = $this->mergeChunks($config, $chunks, $targetFilename);

        if ($config->sweep()) {
            $this->deleteChunkDirectory($config, $uid);
        }

        $this->triggerFileUploadedEvent($config->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }
}
