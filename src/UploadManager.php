<?php

namespace CodingSocks\UploadHandler;

use CodingSocks\UploadHandler\Driver\BlueimpBaseHandler;
use CodingSocks\UploadHandler\Driver\DropzoneBaseHandler;
use CodingSocks\UploadHandler\Driver\FlowJsHandler;
use CodingSocks\UploadHandler\Driver\MonolithBaseHandler;
use CodingSocks\UploadHandler\Driver\NgFileBaseHandler;
use CodingSocks\UploadHandler\Driver\PluploadBaseHandler;
use CodingSocks\UploadHandler\Driver\ResumableJsBaseHandler;
use CodingSocks\UploadHandler\Driver\SimpleUploaderJsHandler;
use Illuminate\Support\Manager;

class UploadManager extends Manager
{
    public function createMonolithDriver()
    {
        return new MonolithBaseHandler($this->container['config']['upload-handler.monolith']);
    }

    public function createBlueimpDriver()
    {
        /** @var \Illuminate\Support\Manager $identityManager */
        $identityManager = $this->container['upload-handler.identity-manager'];

        return new BlueimpBaseHandler($this->container['config']['upload-handler.blueimp'], $identityManager->driver());
    }

    public function createDropzoneDriver()
    {
        return new DropzoneBaseHandler($this->container['config']['upload-handler.dropzone']);
    }

    public function createFlowJsDriver()
    {
        return new FlowJsHandler($this->container['config']['upload-handler.resumable-js'], $this->identityManager()->driver());
    }

    public function createNgFileUploadDriver()
    {
        return new NgFileBaseHandler($this->identityManager()->driver());
    }

    public function createPluploadDriver()
    {
        return new PluploadBaseHandler($this->identityManager()->driver());
    }

    public function createResumableJsDriver()
    {
        return new ResumableJsBaseHandler($this->container['config']['upload-handler.resumable-js'], $this->identityManager()->driver());
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
