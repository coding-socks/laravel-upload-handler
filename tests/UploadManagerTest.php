<?php

namespace CodingSocks\ChunkUploader\Tests;

use CodingSocks\ChunkUploader\Driver\BlueimpUploadDriver;
use CodingSocks\ChunkUploader\Driver\DropzoneUploadDriver;
use CodingSocks\ChunkUploader\Driver\MonolithUploadDriver;
use CodingSocks\ChunkUploader\UploadManager;

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
        $this->assertInstanceOf(MonolithUploadDriver::class, $driver);
    }

    public function testBlueimpDriverCreation()
    {
        $driver = $this->manager->driver('blueimp');
        $this->assertInstanceOf(BlueimpUploadDriver::class, $driver);
    }

    public function testDropzoneDriverCreation()
    {
        $driver = $this->manager->driver('dropzone');
        $this->assertInstanceOf(DropzoneUploadDriver::class, $driver);
    }
}
