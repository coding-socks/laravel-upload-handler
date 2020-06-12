<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LaraCrafts\ChunkUploader\Helper\ChunkHelpers;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use LaraCrafts\ChunkUploader\Range\ContentRange;
use LaraCrafts\ChunkUploader\Response\PercentageJsonResponse;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class BlueimpUploadDriver extends UploadDriver
{
    use ChunkHelpers;

    /**
     * @var string
     */
    private $fileParam;

    /**
     * @var \LaraCrafts\ChunkUploader\Identifier\Identifier
     */
    private $identifier;

    /**
     * BlueimpUploadDriver constructor.
     *
     * @param array $config
     * @param \LaraCrafts\ChunkUploader\Identifier\Identifier $identifier
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

        if ($this->isRequestMethodIn($request, [Request::METHOD_DELETE])) {
            return $this->delete($request, $config);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_HEAD,
            Request::METHOD_OPTIONS,
            Request::METHOD_GET,
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_PATCH,
            Request::METHOD_DELETE,
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
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Request $request, StorageConfig $config): Response
    {
        $download = $request->query('download', false);
        if ($download !== false) {
            $uuid = $request->query($this->fileParam);

            return $this->fileResponse($uuid, $config);
        }

        $request->validate([
            $this->fileParam => 'required',
            'totalSize' => 'required',
        ]);

        $originalFilename = $request->query($this->fileParam);
        $totalSize = $request->query('totalSize');
        $uuid = $this->identifier->generateFileIdentifier($originalFilename, $totalSize);

        if (!$this->chunkExists($config, $uuid)) {
            return new JsonResponse([
                'file' => null,
            ]);
        }

        $chunk = Arr::last($this->chunks($config, $uuid));
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
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
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

        $uuid = $this->identifier->generateFileIdentifier($file->getClientOriginalName(), $range->getTotal());

        $chunks = $this->storeChunk($config, $range, $file, $uuid);

        if (!$range->isLast()) {
            return new PercentageJsonResponse($range->getPercentage());
        }

        $targetFilename = $file->hashName();

        $path = $this->mergeChunks($config, $chunks, $targetFilename);

        if ($config->sweep()) {
            $this->deleteChunkDirectory($config, $uuid);
        }

        $this->triggerFileUploadedEvent($config->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request, StorageConfig $config): Response
    {
        $filename = $request->post($this->fileParam);
        $path = $config->getMergedDirectory() . '/' . $filename;
        Storage::disk($config->getDisk())->delete($path);

        return new Response();
    }
}
