<?php

namespace CodingSocks\UploadHandler\Tests\Driver;

use CodingSocks\UploadHandler\Driver\BlueimpBaseHandler;
use CodingSocks\UploadHandler\Event\FileUploaded;
use CodingSocks\UploadHandler\Exception\InternalServerErrorHttpException;
use CodingSocks\UploadHandler\Tests\TestCase;
use CodingSocks\UploadHandler\UploadHandler;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Constraint\StringContains;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlueimpUploadHandlerTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('config')->set('upload-handler.identifier', 'nop');
        $this->app->make('config')->set('upload-handler.handler', 'blueimp');
        $this->app->make('config')->set('upload-handler.sweep', false);
        $this->handler = $this->app->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = $this->app->make('upload-handler.upload-manager');

        $this->assertInstanceOf(BlueimpBaseHandler::class, $manager->driver());
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

    public function testDownloadWhenFileNotFound()
    {
        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'local-test-file',
            'download' => 1,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->handler->handle($request);
    }

    public function testDownload()
    {
        $this->createFakeLocalFile('merged', 'local-test-file');

        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'local-test-file',
            'download' => 1,
        ]);

        /** @var \Illuminate\Testing\TestResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertStatus(200);

        $this->assertThat($response->headers->get('Content-Disposition'), new StringContains('attachment'));
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('local-test-file', $response->getFile()->getFilename());
    }

    public function testResume()
    {
        $this->createFakeLocalFile('chunks/200_test.txt', '000-099');

        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'test.txt',
            'totalSize' => '200',
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();

        $response->assertJson([
            'file' => [
                'name' => 'test.txt',
                'size' => 100,
            ],
        ]);
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

    public function testUploadFirstChunk()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 0-99/200',
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 50]);

        Storage::disk('local')->assertExists('chunks/200_test.txt/000-099');

        Event::assertNotDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadFirstChunkWithCallback()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 0-99/200',
        ]);

        $callback = $this->createClosureMock($this->never());

        $this->handler->handle($request, $callback);

        Event::assertNotDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadLastChunk()
    {
        $this->createFakeLocalFile('chunks/200_test.txt', '000');

        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-199/200',
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        Storage::disk('local')->assertExists('chunks/200_test.txt/100-199');
        Storage::disk('local')->assertExists($file->hashName('merged'));

        Event::assertDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadLastChunkWithCallback()
    {
        $this->createFakeLocalFile('chunks/200_test.txt', '000');

        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-199/200',
        ]);

        $callback = $this->createClosureMock(
            $this->once(),
            'local',
            $file->hashName('merged')
        );

        $this->handler->handle($request, $callback);

        Event::assertDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
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
