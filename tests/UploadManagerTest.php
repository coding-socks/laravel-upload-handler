<?php

namespace CodingSocks\UploadHandler\Tests;

use CodingSocks\UploadHandler\Driver\BlueimpBaseHandler;
use CodingSocks\UploadHandler\Driver\DropzoneBaseHandler;
use CodingSocks\UploadHandler\Driver\MonolithBaseHandler;
use CodingSocks\UploadHandler\UploadManager;

class UploadManagerTest extends TestCase
{
    /**
     * @var UploadManager
     */
    private $manager;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new UploadManager($this->app);
    }

    public function testMonolithDriverCreation()
    {
        $driver = $this->manager->driver('monolith');
        $this->assertInstanceOf(MonolithBaseHandler::class, $driver);
    }

    public function testBlueimpDriverCreation()
    {
        $driver = $this->manager->driver('blueimp');
        $this->assertInstanceOf(BlueimpBaseHandler::class, $driver);
    }

    public function testDropzoneDriverCreation()
    {
        $driver = $this->manager->driver('dropzone');
        $this->assertInstanceOf(DropzoneBaseHandler::class, $driver);
    }
}
