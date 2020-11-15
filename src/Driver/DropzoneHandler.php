<?php

namespace CodingSocks\UploadHandler\Driver;

use Closure;
use CodingSocks\UploadHandler\Helper\ChunkHelpers;
use CodingSocks\UploadHandler\Range\DropzoneRange;
use CodingSocks\UploadHandler\Response\PercentageJsonResponse;
use CodingSocks\UploadHandler\StorageConfig;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class DropzoneHandler extends BaseHandler
{
    use ChunkHelpers;

    /**
     * @var string
     */
    private $fileParam;

    /**
     * DropzoneDriver constructor.
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
     * @param \CodingSocks\UploadHandler\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        $file = $request->file($this->fileParam);

        $this->validateUploadedFile($file);

        if ($this->isMonolithRequest($request)) {
            return $this->saveMonolith($file, $config, $fileUploaded);
        }

        $this->validateChunkRequest($request);

        return $this->saveChunk($file, $request, $config, $fileUploaded);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    private function isMonolithRequest(Request $request): bool
    {
        return $request->post('dzuuid') === null
            && $request->post('dzchunkindex') === null
            && $request->post('dztotalfilesize') === null
            && $request->post('dzchunksize') === null
            && $request->post('dztotalchunkcount') === null
            && $request->post('dzchunkbyteoffset') === null;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    private function validateChunkRequest(Request $request): void
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
     * @param \Illuminate\Http\UploadedFile $file
     * @param \CodingSocks\UploadHandler\StorageConfig $config
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
     * @param \CodingSocks\UploadHandler\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function saveChunk(UploadedFile $file, Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        try {
            $range = new DropzoneRange(
                $request,
                'dzchunkindex',
                'dztotalchunkcount',
                'dzchunksize',
                'dztotalfilesize'
            );
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $uid = $request->post('dzuuid');

        $chunks = $this->storeChunk($config, $range, $file, $uid);

        if (!$range->isFinished($chunks)) {
            return new PercentageJsonResponse($range->getPercentage($chunks));
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
