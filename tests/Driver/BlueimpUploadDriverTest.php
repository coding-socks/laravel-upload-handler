<?php

namespace LaraCrafts\ChunkUploader\Tests\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Driver\BlueimpUploadDriver;
use LaraCrafts\ChunkUploader\Tests\TestCase;
use LaraCrafts\ChunkUploader\UploadHandler;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        $response = $this->handler->handle($request);

        $response = $this->createTestResponse($response);
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

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 0-99/200',
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\LaraCrafts\ChunkUploader\Response\Response $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 50]);

        $this->assertCount(1, $response->getChunks());
        $this->assertEquals('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt/000-099', $response->getChunks()[0]);
        $this->assertFalse($response->isFinished());
        $this->assertNull($response->getMergedFile());

        Storage::disk('local')->assertExists('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt/000-099');
    }

    public function testUploadLastChunk()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');
        $this->createFakeLocalFile('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt', '000');

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-199/200',
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\LaraCrafts\ChunkUploader\Response\Response $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        $this->assertCount(2, $response->getChunks());
        $this->assertEquals('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt/100-199', $response->getChunks()[1]);
        $this->assertTrue($response->isFinished());
        $this->assertNotNull($response->getMergedFile());

        Storage::disk('local')->assertExists('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt/100-199');
        Storage::disk('local')->assertExists('merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt');
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
