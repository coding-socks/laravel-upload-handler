<?php

namespace LaraCrafts\ChunkUploader;

use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use LaraCrafts\ChunkUploader\Driver\UploadDriver;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use Symfony\Component\HttpFoundation\Response;

class UploadHandler
{
    use Macroable;

    /**
     * @var \LaraCrafts\ChunkUploader\Driver\UploadDriver
     */
    protected $driver;

    /**
     * @var mixed
     */
    protected $config;

    /**
     * @var \LaraCrafts\ChunkUploader\Identifier\Identifier
     */
    protected $identifier;

    /**
     * UploadHandler constructor.
     *
     * @param \LaraCrafts\ChunkUploader\Driver\UploadDriver $driver
     * @param \LaraCrafts\ChunkUploader\Identifier\Identifier $identifier
     * @param array $config
     */
    public function __construct(UploadDriver $driver, Identifier $identifier, array $config)
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
