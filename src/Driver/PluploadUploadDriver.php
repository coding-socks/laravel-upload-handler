<?php

namespace CodingSocks\ChunkUploader\Driver;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use CodingSocks\ChunkUploader\Helper\ChunkHelpers;
use CodingSocks\ChunkUploader\Identifier\Identifier;
use CodingSocks\ChunkUploader\Range\PluploadRange;
use CodingSocks\ChunkUploader\Response\PercentageJsonResponse;
use CodingSocks\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class PluploadUploadDriver extends UploadDriver
{
    use ChunkHelpers;

    /**
     * @var \CodingSocks\ChunkUploader\Identifier\Identifier
     */
    private $identifier;

    /**
     * PluploadUploadDriver constructor.
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
        if ($this->isRequestMethodIn($request, [Request::METHOD_POST])) {
            return $this->save($request, $config, $fileUploaded);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_POST,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \CodingSocks\ChunkUploader\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return mixed
     */
    private function save(Request $request, StorageConfig $config, ?Closure $fileUploaded)
    {
        $file = $request->file('file');

        $this->validateUploadedFile($file);

        $this->validateChunkRequest($request);

        return $this->saveChunk($file, $request, $config, $fileUploaded);
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    private function validateChunkRequest(Request $request): void
    {
        $request->validate([
            'name' => 'required',
            'chunk' => 'required|integer',
            'chunks' => 'required|integer',
        ]);
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
           $range = new PluploadRange($request);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $uuid = $this->identifier->generateFileIdentifier($range->getTotal(), $file->getClientOriginalName());

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

        return new PercentageJsonResponse($range->getPercentage());
    }
}
