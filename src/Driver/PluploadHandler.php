<?php

namespace CodingSocks\UploadHandler\Driver;

use Closure;
use CodingSocks\UploadHandler\Helper\ChunkHelpers;
use CodingSocks\UploadHandler\Identifier\Identifier;
use CodingSocks\UploadHandler\Range\PluploadRange;
use CodingSocks\UploadHandler\Response\PercentageJsonResponse;
use CodingSocks\UploadHandler\StorageConfig;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class PluploadHandler extends BaseHandler
{
    use ChunkHelpers;

    /**
     * @var \CodingSocks\UploadHandler\Identifier\Identifier
     */
    private $identifier;

    /**
     * PluploadDriver constructor.
     *
     * @param \CodingSocks\UploadHandler\Identifier\Identifier $identifier
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
     * @param \CodingSocks\UploadHandler\StorageConfig $config
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
     * @param \CodingSocks\UploadHandler\StorageConfig $config
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

        return new PercentageJsonResponse($range->getPercentage());
    }
}
