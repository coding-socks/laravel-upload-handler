<?php

namespace CodingSocks\ChunkUploader;

use Illuminate\Support\Manager;
use CodingSocks\ChunkUploader\Identifier\SessionIdentifier;

class IdentityManager extends Manager
{
    public function createSessionDriver()
    {
        return new SessionIdentifier();
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['chunk-uploader.identifier'];
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
        $this->app['config']['chunk-uploader.identifier'] = $name;
    }
}
