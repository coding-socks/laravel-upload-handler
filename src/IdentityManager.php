<?php

namespace CodingSocks\UploadHandler;

use CodingSocks\UploadHandler\Identifier\AuthIdentifier;
use CodingSocks\UploadHandler\Identifier\NopIdentifier;
use CodingSocks\UploadHandler\Identifier\SessionIdentifier;
use Illuminate\Support\Manager;

class IdentityManager extends Manager
{
    public function createSessionDriver()
    {
        return new SessionIdentifier();
    }

    public function createAuthDriver()
    {
        return new AuthIdentifier();
    }

    public function createNopDriver()
    {
        return new NopIdentifier();
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->container['config']['upload-handler.identifier'];
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
        $this->container['config']['upload-handler.identifier'] = $name;
    }
}
