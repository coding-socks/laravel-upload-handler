<?php

namespace LaraCrafts\ChunkUploader\Tests\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Driver\MonolithUploadDriver;
use LaraCrafts\ChunkUploader\Event\FileUploaded;
use LaraCrafts\ChunkUploader\Exception\InternalServerErrorHttpException;
use LaraCrafts\ChunkUploader\Tests\TestCase;
use LaraCrafts\ChunkUploader\UploadHandler;
use Mockery;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MonolithUploadDriverTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->app->make('config')->set('chunk-uploader.uploader', 'monolith');
        $this->handler = $this->app->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = $this->app->make('chunk-uploader.upload-manager');

        $this->assertInstanceOf(MonolithUploadDriver::class, $manager->driver());
    }

    public function testDownload()
    {
        $this->createFakeLocalFile('merged', 'local-test-file');

        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'local-test-file',
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();

        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('local-test-file', $response->getFile()->getFilename());
    }

    public function testDownloadWhenFileNotFound()
    {
        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'local-test-file',
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->handler->handle($request);
    }

    public function testUploadWhenFileParameterIsEmpty()
    {
        $request = Request::create('', Request::METHOD_POST);

        $this->expectException(BadRequestHttpException::class);

        $this->handler->handle($request);
    }

    public function testUploadWhenFileParameterIsInvalid()
    {
        $file = Mockery::mock(UploadedFile::class)->makePartial();
        $file->shouldReceive('isValid')
            ->andReturn(false);

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ]);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->handler->handle($request);
    }

    public function testUpload()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 20),
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();

        Storage::disk('local')->assertExists('merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt');

        Event::assertDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt';
        });
    }

    public function testUploadWithCallback()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 20),
        ]);

        $callback = $this->createClosureMock(
            $this->once(),
            'local',
            'merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt'
        );

        $this->handler->handle($request, $callback);

        Event::assertDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt';
        });
    }

    public function testDelete()
    {
        $this->createFakeLocalFile('merged', 'local-test-file');

        $request = Request::create('', Request::METHOD_DELETE, [
            'file' => 'local-test-file',
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();

        Storage::disk('local')->assertMissing('merged/local-test-file');
    }
}
