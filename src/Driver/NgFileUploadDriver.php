<?php

namespace CodingSocks\ChunkUploader\Driver;

use Closure;
use CodingSocks\ChunkUploader\Helper\ChunkHelpers;
use CodingSocks\ChunkUploader\Identifier\Identifier;
use CodingSocks\ChunkUploader\Range\NgFileUploadRange;
use CodingSocks\ChunkUploader\Response\PercentageJsonResponse;
use CodingSocks\ChunkUploader\StorageConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class NgFileUploadDriver extends UploadDriver
{
    use ChunkHelpers;

    /**
     * @var \CodingSocks\ChunkUploader\Identifier\Identifier
     */
    private $identifier;

    /**
     * NgFileUploadDriver constructor.
     *
     * @param \CodingSocks\ChunkUploader\Identifier\Identifier $identifier
     */
    public function __construct(Identifier $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        if ($this->isRequestMethodIn($request, [Request::METHOD_GET])) {
            return $this->resume($request, $config);
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_POST])) {
            return $this->save($request, $config, $fileUploaded);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_GET,
            Request::METHOD_POST,
        ]);
    }

    private function resume(Request $request, StorageConfig $config): Response
    {
        $request->validate([
            'file' => 'required',
            'totalSize' => 'required',
        ]);

        $originalFilename = $request->get('file');
        $totalSize = $request->get('totalSize');
        $uid = $this->identifier->generateFileIdentifier($totalSize, $originalFilename);

        if (!$this->chunkExists($config, $uid)) {
            return new JsonResponse([
                'file' => $originalFilename,
                'size' => 0,
            ]);
        }

        $chunk = Arr::last($this->chunks($config, $uid));
        $size = explode('-', basename($chunk))[1] + 1;

        return new JsonResponse([
            'file' => $originalFilename,
            'size' => $size,
        ]);
    }

    private function save(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        $file = $request->file('file');

        $this->validateUploadedFile($file);

        if ($this->isMonolithRequest($request)) {
            return $this->saveMonolith($file, $config, $fileUploaded);
        }

        $this->validateChunkRequest($request);

        return $this->saveChunk($file, $request, $config, $fileUploaded);
    }

    private function isMonolithRequest(Request $request)
    {
        return empty($request->post());
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    private function validateChunkRequest(Request $request): void
    {
        $request->validate([
            '_chunkNumber' => 'required|numeric',
            '_chunkSize' => 'required|numeric',
            '_totalSize' => 'required|numeric',
            '_currentChunkSize' => 'required|numeric',
        ]);
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param \CodingSocks\ChunkUploader\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function saveMonolith(UploadedFile $file, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        $path = $file->store($config->getMergedDirectory(), [
            'disk' => $config->getDisk(),
        ]);

        $this->triggerFileUploadedEvent($config->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param \Illuminate\Http\Request $request
     * @param \CodingSocks\ChunkUploader\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function saveChunk(UploadedFile $file, Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        try {
            $range = new NgFileUploadRange($request);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $originalFilename = $file->getClientOriginalName();
        $totalSize = $request->get('_totalSize');
        $uid = $this->identifier->generateFileIdentifier($totalSize, $originalFilename);

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
