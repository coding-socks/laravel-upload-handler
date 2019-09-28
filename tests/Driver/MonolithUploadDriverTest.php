<?php

namespace LaraCrafts\ChunkUploader\Tests\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Driver\MonolithUploadDriver;
use LaraCrafts\ChunkUploader\Event\FileUploaded;
use LaraCrafts\ChunkUploader\Tests\TestCase;
use LaraCrafts\ChunkUploader\UploadHandler;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
    }

    public function testDriverInstance()
    {
        $manager = $this->app->make('chunk-uploader.upload-manager');

        $this->assertInstanceOf(MonolithUploadDriver::class, $manager->driver());
    }

    public function testDownload()
    {
        Storage::fake('local');
        $this->createFakeLocalFile('merged', 'local-test-file');

        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'local-test-file',
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertStatus(200);

        $this->assertContains('attachment', $response->headers->get('Content-Disposition'));
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('local-test-file', $response->getFile()->getFilename());
    }

    public function testUpload()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');

        Event::fake();

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 20),
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\LaraCrafts\ChunkUploader\Response\Response $response */
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
        Storage::fake('local');

        Event::fake();

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
        Storage::fake('local');
        $this->createFakeLocalFile('merged', 'local-test-file');

        $request = Request::create('', Request::METHOD_DELETE, [
            'file' => 'local-test-file',
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();

        Storage::disk('local')->assertMissing('merged/local-test-file');
    }
}
