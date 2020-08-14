<?php

namespace CodingSocks\UploadHandler;

use Closure;
use CodingSocks\UploadHandler\Driver\BaseHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\HttpFoundation\Response;

class UploadHandler
{
    use Macroable;

    /**
     * @var \CodingSocks\UploadHandler\Driver\BaseHandler
     */
    protected $driver;

    /**
     * @var StorageConfig
     */
    protected $config;

    /**
     * UploadHandler constructor.
     *
     * @param \CodingSocks\UploadHandler\Driver\BaseHandler $driver
     * @param StorageConfig $config
     */
    public function __construct(BaseHandler $driver, StorageConfig $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    /**
     * Save an uploaded file to the target directory.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $fileUploaded
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $fileUploaded = null): Response
    {
        return $this->driver->handle($request, $this->config, $fileUploaded);
    }
}
