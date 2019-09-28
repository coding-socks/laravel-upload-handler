<?php

namespace LaraCrafts\ChunkUploader;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use LaraCrafts\ChunkUploader\Driver\UploadDriver;
use Symfony\Component\HttpFoundation\Response;

class UploadHandler
{
    use Macroable;

    /**
     * @var \LaraCrafts\ChunkUploader\Driver\UploadDriver
     */
    protected $driver;

    /**
     * @var StorageConfig
     */
    protected $config;

    /**
     * UploadHandler constructor.
     *
     * @param \LaraCrafts\ChunkUploader\Driver\UploadDriver $driver
     * @param StorageConfig $config
     */
    public function __construct(UploadDriver $driver, StorageConfig $config)
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
