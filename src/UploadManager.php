<?php

namespace LaraCrafts\ChunkUploader;

use Illuminate\Support\Manager;
use LaraCrafts\ChunkUploader\Drivers\BlueimpUploadDriver;
use LaraCrafts\ChunkUploader\Drivers\DropzoneUploadDriver;
use LaraCrafts\ChunkUploader\Drivers\MonolithUploadDriver;

class UploadManager extends Manager
{
    public function createMonolithDriver()
    {
        return new MonolithUploadDriver($this->app['config']['chunk-uploader.monolith']);
    }

    public function createBlueimpDriver()
    {
        return new BlueimpUploadDriver($this->app['config']['chunk-uploader.blueimp']);
    }

    public function createDropzoneDriver()
    {
        return new DropzoneUploadDriver($this->app['config']['chunk-uploader.dropzone']);
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
