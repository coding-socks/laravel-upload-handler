<?php

namespace LaraCrafts\ChunkUploader;

use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use LaraCrafts\ChunkUploader\Drivers\UploadDriver;
use LaraCrafts\ChunkUploader\Identifiers\Identifier;
use Symfony\Component\HttpFoundation\Response;

class UploadHandler
{
    use Macroable;

    /**
     * @var \LaraCrafts\ChunkUploader\Drivers\UploadDriver
     */
    protected $driver;

    /**
     * @var StorageConfig
     */
    protected $config;

    /**
     * @var \LaraCrafts\ChunkUploader\Identifiers\Identifier
     */
    protected $identifier;

    /**
     * UploadHandler constructor.
     *
     * @param \LaraCrafts\ChunkUploader\Drivers\UploadDriver $driver
     * @param \LaraCrafts\ChunkUploader\Identifiers\Identifier $identifier
     * @param StorageConfig $config
     */
    public function __construct(UploadDriver $driver, Identifier $identifier, StorageConfig $config)
    {
        $this->driver = $driver;
        $this->config = $config;
        $this->identifier = $identifier;
    }

    /**
     * Save an uploaded file to the target directory.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request): Response
    {
        return $this->driver->handle($request, $this->identifier, $this->config);
    }
}
