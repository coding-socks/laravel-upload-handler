<?php

namespace LaraCrafts\ChunkUploader\Tests\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Driver\BlueimpUploadDriver;
use LaraCrafts\ChunkUploader\Event\FileUploaded;
use LaraCrafts\ChunkUploader\Tests\TestCase;
use LaraCrafts\ChunkUploader\UploadHandler;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class BlueimpUploadDriverTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->app->make('config')->set('chunk-uploader.uploader', 'blueimp');
        $this->app->make('config')->set('chunk-uploader.sweep', false);
        $this->handler = $this->app->make(UploadHandler::class);
    }

    public function testDriverInstance()
    {
        $manager = $this->app->make('chunk-uploader.upload-manager');

        $this->assertInstanceOf(BlueimpUploadDriver::class, $manager->driver());
    }

    public function testInfo()
    {
        $request = Request::create('', Request::METHOD_HEAD);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();

        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Content-Disposition', 'inline; filename="files.json"');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Vary', 'Accept');
    }

    public function testDownload()
    {
        Storage::fake('local');
        $this->createFakeLocalFile('merged', 'local-test-file');

        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'local-test-file',
            'download' => 1,
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="local-test-file"');

        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('local-test-file', $response->getFile()->getFilename());
    }

    public function testResume()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');
        $this->createFakeLocalFile('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt', '000-099');

        $request = Request::create('', Request::METHOD_GET, [
            'file' => '2494cefe4d234bd331aeb4514fe97d810efba29b.txt',
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();

        $response->assertJson(['file' => [
            'name' => '2494cefe4d234bd331aeb4514fe97d810efba29b.txt',
            'size' => 100,
        ]]);
    }

    public function testUploadFirstChunk()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');

        Event::fake();

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 0-99/200',
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\LaraCrafts\ChunkUploader\Response\Response $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 50]);

        Storage::disk('local')->assertExists('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt/000-099');

        Event::assertNotDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt';
        });
    }

    public function testUploadFirstChunkWithCallback()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');

        Event::fake();

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 0-99/200',
        ]);

        $callback = $this->createClosureMock($this->never());

        $this->handler->handle($request, $callback);

        Event::assertNotDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt';
        });
    }

    public function testUploadLastChunk()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');
        $this->createFakeLocalFile('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt', '000');

        Event::fake();

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-199/200',
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\LaraCrafts\ChunkUploader\Response\Response $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        Storage::disk('local')->assertExists('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt/100-199');
        Storage::disk('local')->assertExists('merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt');

        Event::assertDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt';
        });
    }

    public function testUploadLastChunkWithCallback()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');
        $this->createFakeLocalFile('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt', '000');

        Event::fake();

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-199/200',
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
