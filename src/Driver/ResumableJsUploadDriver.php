<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use LaraCrafts\ChunkUploader\Helper\ChunkHelpers;
use LaraCrafts\ChunkUploader\Range\OneBasedRequestBodyRange;
use LaraCrafts\ChunkUploader\Response\PercentageJsonResponse;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResumableJsUploadDriver extends UploadDriver
{
    use ChunkHelpers;

    /**
     * @var string
     */
    private $fileParam;

    /**
     * @var string
     */
    private $uploadMethod;

    /**
     * @var string
     */
    private $testMethod;

    /**
     * @var string
     */
    private $parameterNamespace;

    /**
     * @var string[]
     */
    private $parameterNames;

    /**
     * ResumableJsUploadDriver constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->fileParam = $config['param'];

        $this->uploadMethod = $config['upload-method'];
        $this->testMethod = $config['test-method'];

        $this->parameterNamespace = $config['parameter-namespace'];
        $this->parameterNames = $config['parameter-names'];
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        if ($this->isRequestMethodIn($request, [$this->testMethod])) {
            return $this->resume($request, $storageConfig);
        }

        if ($this->isRequestMethodIn($request, [$this->uploadMethod])) {
            return $this->save($request, $storageConfig, $fileUploaded);
        }

        throw new MethodNotAllowedHttpException([
            $this->uploadMethod,
            $this->testMethod,
        ]);
    }

    /**
     * @param Request $request
     * @param StorageConfig $storageConfig
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resume(Request $request, StorageConfig $storageConfig): Response
    {
        $this->validateChunkRequest($request);

        try {
            $range = new OneBasedRequestBodyRange(
                $request->query,
                $this->buildParameterName('chunk-number'),
                $this->buildParameterName('total-chunks'),
                $this->buildParameterName('chunk-size'),
                $this->buildParameterName('total-size')
            );
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $filename = $request->query($this->buildParameterName('identifier'));
        $chunkname = $this->buildChunkname($range);

        if (! $this->chunkExists($storageConfig, $filename, $chunkname)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse(['OK']);
    }

    /**
     * @param Request $request
     * @param StorageConfig $storageConfig
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save(Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        $file = $request->file($this->fileParam);

        $this->validateUploadedFile($file);

        $this->validateChunkRequest($request);

        return $this->saveChunk($file, $request, $storageConfig, $fileUploaded);
    }

    /**
     * @param Request $request
     */
    private function validateChunkRequest(Request $request)
    {
        $validation = [];

        foreach ($this->parameterNames as $key => $_) {
            $validation[$this->buildParameterName($key)] = 'required';
        }

        $request->validate($validation);
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
            $range = new OneBasedRequestBodyRange(
                $request,
                $this->buildParameterName('chunk-number'),
                $this->buildParameterName('total-chunks'),
                $this->buildParameterName('chunk-size'),
                $this->buildParameterName('total-size')
            );
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $uuid = $request->post($this->buildParameterName('identifier'));

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

    /**
     * @param $key string
     *
     * @return string
     */
    private function buildParameterName(string $key): string
    {
        if (! array_key_exists($key, $this->parameterNames)) {
            throw new InvalidArgumentException(sprintf('`%s` is an invalid key for parameter name', $key));
        }

        return $this->parameterNamespace . $this->parameterNames[$key];
    }
}
