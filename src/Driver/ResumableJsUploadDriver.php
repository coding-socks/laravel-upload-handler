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
    public function handle(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        if ($this->isRequestMethodIn($request, [$this->testMethod])) {
            return $this->resume($request, $config);
        }

        if ($this->isRequestMethodIn($request, [$this->uploadMethod])) {
            return $this->save($request, $config, $fileUploaded);
        }

        throw new MethodNotAllowedHttpException([
            $this->uploadMethod,
            $this->testMethod,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resume(Request $request, StorageConfig $config): Response
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

        if (! $this->chunkExists($config, $filename, $chunkname)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse(['OK']);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        $file = $request->file($this->fileParam);

        $this->validateUploadedFile($file);

        $this->validateChunkRequest($request);

        return $this->saveChunk($file, $request, $config, $fileUploaded);
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
     * @param UploadedFile $file
     * @param Request $request
     * @param StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return Response
     */
    private function saveChunk(UploadedFile $file, Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
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

        $filename = $request->post($this->buildParameterName('identifier'));

        $chunks = $this->storeChunk($config, $range, $file, $filename);

        if (!$range->isFinished($chunks)) {
            return new PercentageJsonResponse($range->getPercentage($chunks));
        }

        $targetFilename = $filename;

        // On windows you can not create a file whose name ends with a dot
        if ($file->getClientOriginalExtension()) {
            $targetFilename .= '.' . $file->getClientOriginalExtension();
        }

        $path = $this->mergeChunks($config, $chunks, $targetFilename);

        if ($config->sweep()) {
            $this->deleteChunkDirectory($config, $filename);
        }

        $this->triggerFileUploadedEvent($config->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }

    private function buildParameterName($key)
    {
        if (! array_key_exists($key, $this->parameterNames)) {
            throw new InvalidArgumentException(sprintf('`%s` is an invalid key for parameter name', $key));
        }

        return $this->parameterNamespace . $this->parameterNames[$key];
    }
}
