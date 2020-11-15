<?php

namespace CodingSocks\UploadHandler;

use CodingSocks\UploadHandler\Driver\BlueimpHandler;
use CodingSocks\UploadHandler\Driver\DropzoneHandler;
use CodingSocks\UploadHandler\Driver\FlowJsHandler;
use CodingSocks\UploadHandler\Driver\MonolithHandler;
use CodingSocks\UploadHandler\Driver\NgFileHandler;
use CodingSocks\UploadHandler\Driver\PluploadHandler;
use CodingSocks\UploadHandler\Driver\ResumableJsHandler;
use CodingSocks\UploadHandler\Driver\SimpleUploaderJsHandler;
use Illuminate\Support\Manager;

class UploadManager extends Manager
{
    public function createMonolithDriver()
    {
        return new MonolithHandler($this->container['config']['upload-handler.monolith']);
    }

    public function createBlueimpDriver()
    {
        /** @var \Illuminate\Support\Manager $identityManager */
        $identityManager = $this->container['upload-handler.identity-manager'];

        return new BlueimpHandler($this->container['config']['upload-handler.blueimp'], $identityManager->driver());
    }

    public function createDropzoneDriver()
    {
        return new DropzoneHandler($this->container['config']['upload-handler.dropzone']);
    }

    public function createFlowJsDriver()
    {
        return new FlowJsHandler($this->container['config']['upload-handler.resumable-js'], $this->identityManager()->driver());
    }

    public function createNgFileUploadDriver()
    {
        return new NgFileHandler($this->identityManager()->driver());
    }

    public function createPluploadDriver()
    {
        return new PluploadHandler($this->identityManager()->driver());
    }

    public function createResumableJsDriver()
    {
        return new ResumableJsHandler($this->container['config']['upload-handler.resumable-js'], $this->identityManager()->driver());
    }

    public function createSimpleUploaderJsDriver()
    {
        return new SimpleUploaderJsHandler($this->container['config']['upload-handler.simple-uploader-js'], $this->identityManager()->driver());
    }

    /**
     * @return \Illuminate\Support\Manager
     */
    protected function identityManager()
    {
        return $this->container['upload-handler.identity-manager'];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->container['config']['upload-handler.handler'];
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
        $this->container['config']['upload-handler.handler'] = $name;
    }
}
