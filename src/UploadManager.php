<?php

namespace CodingSocks\ChunkUploader;

use CodingSocks\ChunkUploader\Driver\BlueimpUploadDriver;
use CodingSocks\ChunkUploader\Driver\DropzoneUploadDriver;
use CodingSocks\ChunkUploader\Driver\FlowJsUploadDriver;
use CodingSocks\ChunkUploader\Driver\MonolithUploadDriver;
use CodingSocks\ChunkUploader\Driver\PluploadUploadDriver;
use CodingSocks\ChunkUploader\Driver\ResumableJsUploadDriver;
use CodingSocks\ChunkUploader\Driver\SimpleUploaderJsUploadDriver;
use Illuminate\Support\Manager;

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

    public function createFlowJsDriver()
    {
        return new FlowJsUploadDriver($this->app['config']['chunk-uploader.resumable-js']);
    }

    public function createPluploadDriver()
    {
        /** @var \Illuminate\Support\Manager $identityManager */
        $identityManager = $this->app['chunk-uploader.identity-manager'];

        return new PluploadUploadDriver($identityManager->driver());
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
