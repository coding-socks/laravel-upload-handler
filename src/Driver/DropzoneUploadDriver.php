<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use LaraCrafts\ChunkUploader\Helper\ChunkHelpers;
use LaraCrafts\ChunkUploader\Range\ZeroBasedRequestBodyRange;
use LaraCrafts\ChunkUploader\Response\PercentageJsonResponse;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class DropzoneUploadDriver extends UploadDriver
{
    use ChunkHelpers;

    /**
     * @var string
     */
    private $fileParam;

    /**
     * DropzoneUploadDriver constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->fileParam = $config['param'];
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        if ($this->isRequestMethodIn($request, [Request::METHOD_POST])) {
            return $this->save($request, $storageConfig, $fileUploaded);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_POST,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param StorageConfig $storageConfig
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save(Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        $file = $request->file($this->fileParam);

        $this->validateUploadedFile($file);

        if ($this->isMonolithRequest($request)) {
            return $this->saveMonolith($file, $storageConfig, $fileUploaded);
        }

        $this->validateChunkRequest($request);

        return $this->saveChunk($file, $request, $storageConfig, $fileUploaded);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function isMonolithRequest(Request $request)
    {
        return $request->post('dzuuid') === null
            && $request->post('dzchunkindex') === null
            && $request->post('dztotalfilesize') === null
            && $request->post('dzchunksize') === null
            && $request->post('dztotalchunkcount') === null
            && $request->post('dzchunkbyteoffset') === null;
    }

    /**
     * @param Request $request
     */
    private function validateChunkRequest(Request $request)
    {
        $request->validate([
            'dzuuid' => 'required',
            'dzchunkindex' => 'required',
            'dztotalfilesize' => 'required',
            'dzchunksize' => 'required',
            'dztotalchunkcount' => 'required',
            'dzchunkbyteoffset' => 'required',
        ]);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param StorageConfig $storageConfig
     * @param \Closure|null $fileUploaded
     *
     * @return Response
     */
    private function saveMonolith(UploadedFile $uploadedFile, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        $path = $uploadedFile->store($storageConfig->getMergedDirectory(), [
            'disk' => $storageConfig->getDisk(),
        ]);

        $this->triggerFileUploadedEvent($storageConfig->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Request $request
     * @param StorageConfig $storageConfig
     * @param \Closure|null $fileUploaded
     *
     * @return Response
     */
    private function saveChunk(UploadedFile $uploadedFile, Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        try {
            $range = new ZeroBasedRequestBodyRange(
                $request,
                'dzchunkindex',
                'dztotalchunkcount',
                'dzchunksize',
                'dztotalfilesize'
            );
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $uuid = $request->post('dzuuid');

        $chunks = $this->storeChunk($storageConfig, $range, $uploadedFile, $uuid);

        if (!$range->isFinished($chunks)) {
            return new PercentageJsonResponse($range->getPercentage($chunks));
        }

        $targetFilename = $uploadedFile->hashName();

        $path = $this->mergeChunks($storageConfig, $chunks, $targetFilename);

        if ($storageConfig->sweep()) {
            $this->deleteChunkDirectory($storageConfig, $uuid);
        }

        $this->triggerFileUploadedEvent($storageConfig->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }
}
