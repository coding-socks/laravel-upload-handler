<?php

namespace CodingSocks\UploadHandler\Tests;

use CodingSocks\UploadHandler\Driver\BlueimpHandler;
use CodingSocks\UploadHandler\Driver\DropzoneHandler;
use CodingSocks\UploadHandler\Driver\FlowJsHandler;
use CodingSocks\UploadHandler\Driver\MonolithHandler;
use CodingSocks\UploadHandler\Driver\NgFileHandler;
use CodingSocks\UploadHandler\Driver\PluploadHandler;
use CodingSocks\UploadHandler\Driver\ResumableJsHandler;
use CodingSocks\UploadHandler\Driver\SimpleUploaderJsHandler;
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

    public static function availableDrivers()
    {
        return [
            'monolith' => ['monolith', MonolithHandler::class],
            'blueimp' => ['blueimp', BlueimpHandler::class],
            'dropzone' => ['dropzone', DropzoneHandler::class],
            'flow-js' => ['flow-js', FlowJsHandler::class],
            'ng-file-upload' => ['ng-file-upload', NgFileHandler::class],
            'plupload' => ['plupload', PluploadHandler::class],
            'resumable-js' => ['resumable-js', ResumableJsHandler::class],
            'simple-uploader-js' => ['simple-uploader-js', SimpleUploaderJsHandler::class],
        ];
    }

    /**
     * @dataProvider availableDrivers
     *
     * @param $driverName
     * @param $expectedInstanceOf
     */
    public function testDriverCreation($driverName, $expectedInstanceOf)
    {
        $driver = $this->manager->driver($driverName);
        $this->assertInstanceOf($expectedInstanceOf, $driver);
    }

    public function testDefaultDriver()
    {
        $driver = $this->manager->driver();
        $this->assertInstanceOf(MonolithHandler::class, $driver);
    }
}
