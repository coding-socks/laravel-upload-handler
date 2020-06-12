<?php

namespace LaraCrafts\ChunkUploader;

use Illuminate\Support\Manager;
use LaraCrafts\ChunkUploader\Driver\BlueimpUploadDriver;
use LaraCrafts\ChunkUploader\Driver\DropzoneUploadDriver;
use LaraCrafts\ChunkUploader\Driver\MonolithUploadDriver;
use LaraCrafts\ChunkUploader\Driver\ResumableJsUploadDriver;
use LaraCrafts\ChunkUploader\Driver\SimpleUploaderJsUploadDriver;

class UploadManager extends Manager
{
    public function createMonolithDriver()
    {
        return new MonolithUploadDriver($this->app['config']['chunk-uploader.monolith']);
    }

    public function createBlueimpDriver()
    {
        /** @var \Illuminate\Support\Manager $identityManager */
        $identityManager = $this->app['chunk-uploader.identity-manager'];

        return new BlueimpUploadDriver($this->app['config']['chunk-uploader.blueimp'], $identityManager->driver());
    }

    public function createDropzoneDriver()
    {
        return new DropzoneUploadDriver($this->app['config']['chunk-uploader.dropzone']);
    }

    public function createResumableJsDriver()
    {
        return new ResumableJsUploadDriver($this->app['config']['chunk-uploader.resumable-js']);
    }

    public function createSimpleUploaderJsDriver()
    {
        return new SimpleUploaderJsUploadDriver($this->app['config']['chunk-uploader.simple-uploader-js']);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['chunk-uploader.uploader'];
    }

    /**
     * Set the default mail driver name.
     *
     * @param  string $name
     *
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['chunk-uploader.uploader'] = $name;
    }
}
