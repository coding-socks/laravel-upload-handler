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
        return new MonolithBaseHandler($this->app['config']['upload-handler.monolith']);
    }

    public function createBlueimpDriver()
    {
        /** @var \Illuminate\Support\Manager $identityManager */
        $identityManager = $this->app['upload-handler.identity-manager'];

        return new BlueimpBaseHandler($this->app['config']['upload-handler.blueimp'], $identityManager->driver());
    }

    public function createDropzoneDriver()
    {
        return new DropzoneBaseHandler($this->app['config']['upload-handler.dropzone']);
    }

    public function createFlowJsDriver()
    {
        return new FlowJsHandler($this->app['config']['upload-handler.resumable-js'], $this->identityManager()->driver());
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
        return new ResumableJsBaseHandler($this->app['config']['upload-handler.resumable-js'], $this->identityManager()->driver());
    }

    public function createSimpleUploaderJsDriver()
    {
        return new SimpleUploaderJsHandler($this->app['config']['upload-handler.simple-uploader-js'], $this->identityManager()->driver());
    }

    /**
     * @return \Illuminate\Support\Manager
     */
    protected function identityManager()
    {
        return $this->app['upload-handler.identity-manager'];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['upload-handler.handler'];
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
        $this->app['config']['upload-handler.handler'] = $name;
    }
}
