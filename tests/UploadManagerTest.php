<?php

namespace LaraCrafts\ChunkUploader\Tests;

use LaraCrafts\ChunkUploader\Drivers\BlueimpUploadDriver;
use LaraCrafts\ChunkUploader\Drivers\DropzoneUploadDriver;
use LaraCrafts\ChunkUploader\Drivers\MonolithUploadDriver;
use LaraCrafts\ChunkUploader\UploadManager;

class UploadManagerTest extends TestCase
{
    /**
     * @var UploadManager
     */
    private $manager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
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
