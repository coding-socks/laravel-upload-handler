<?php

namespace CodingSocks\UploadHandler\Driver;

use Closure;
use CodingSocks\UploadHandler\Response\PercentageJsonResponse;
use CodingSocks\UploadHandler\StorageConfig;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class MonolithHandler extends BaseHandler
{
    /**
     * @var string
     */
    private $fileParam;

    /**
     * MonolithDriver constructor.
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
        if ($request->isMethod(Request::METHOD_POST)) {
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

        $path = $file->store($config->getMergedDirectory(), [
            'disk' => $config->getDisk(),
        ]);

        $this->triggerFileUploadedEvent($config->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }
}
