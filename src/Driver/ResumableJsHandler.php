<?php

namespace CodingSocks\UploadHandler\Driver;

use Closure;
use CodingSocks\UploadHandler\Helper\ChunkHelpers;
use CodingSocks\UploadHandler\Identifier\Identifier;
use CodingSocks\UploadHandler\Range\ResumableJsRange;
use CodingSocks\UploadHandler\Response\PercentageJsonResponse;
use CodingSocks\UploadHandler\StorageConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ResumableJsHandler extends BaseHandler
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
     * ResumableJsDriver constructor.
     *
     * @param array $config
     * @param \CodingSocks\UploadHandler\Identifier\Identifier $identifier
     */
    public function __construct($config, Identifier $identifier)
    {
        $this->fileParam = $config['param'];
        $this->identifier = $identifier;

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
     * @param \CodingSocks\UploadHandler\StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resume(Request $request, StorageConfig $config): Response
    {
        $this->validateChunkRequest($request);

        try {
            $range = new ResumableJsRange(
                $request->query,
                $this->buildParameterName('chunk-number'),
                $this->buildParameterName('total-chunks'),
                $this->buildParameterName('chunk-size'),
                $this->buildParameterName('total-size')
            );
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $uid = $this->identifier->generateIdentifier($request->query($this->buildParameterName('identifier')));
        $chunkname = $this->buildChunkname($range);

        if (! $this->chunkExists($config, $uid, $chunkname)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(['OK']);
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

        $this->validateChunkRequest($request);

        return $this->saveChunk($file, $request, $config, $fileUploaded);
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    private function validateChunkRequest(Request $request): void
    {
        $validation = [];

        foreach ($this->parameterNames as $key => $_) {
            $validation[$this->buildParameterName($key)] = 'required';
        }

        $request->validate($validation);
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
            $range = new ResumableJsRange(
                $request,
                $this->buildParameterName('chunk-number'),
                $this->buildParameterName('total-chunks'),
                $this->buildParameterName('chunk-size'),
                $this->buildParameterName('total-size')
            );
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $weakId = $request->post($this->buildParameterName('identifier'));
        $uid = $this->identifier->generateIdentifier($weakId);

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
