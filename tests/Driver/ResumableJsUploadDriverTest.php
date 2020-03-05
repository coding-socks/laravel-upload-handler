<?php

namespace LaraCrafts\ChunkUploader\Tests\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LaraCrafts\ChunkUploader\Driver\ResumableJsUploadDriver;
use LaraCrafts\ChunkUploader\Event\FileUploaded;
use LaraCrafts\ChunkUploader\Exception\InternalServerErrorHttpException;
use LaraCrafts\ChunkUploader\Tests\TestCase;
use LaraCrafts\ChunkUploader\UploadHandler;
use Mockery;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResumableJsUploadDriverTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('config')->set('chunk-uploader.uploader', 'resumable-js');
        $this->app->make('config')->set('chunk-uploader.sweep', false);
        $this->handler = $this->app->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = $this->app->make('chunk-uploader.upload-manager');

        $this->assertInstanceOf(ResumableJsUploadDriver::class, $manager->driver());
    }

    public function testResumeWhenChunkDoesNotExists()
    {
        $this->createFakeLocalFile('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt', '000-099');

        $request = Request::create('', Request::METHOD_GET, [
            'resumableChunkNumber' => 2,
            'resumableTotalChunks' => 2,
            'resumableChunkSize' => 100,
            'resumableTotalSize' => 200,
            'resumableIdentifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'resumableFilename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableRelativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableCurrentChunkSize' => 100,
            'resumableType' => 'text/plain',
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->createTestResponse($this->handler->handle($request));
    }

    public function testResume()
    {
        $this->createFakeLocalFile('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt', '000-099');

        $request = Request::create('', Request::METHOD_GET, [
            'resumableChunkNumber' => 1,
            'resumableTotalChunks' => 2,
            'resumableChunkSize' => 100,
            'resumableTotalSize' => 200,
            'resumableIdentifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'resumableFilename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableRelativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableCurrentChunkSize' => 100,
            'resumableType' => 'text/plain',
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
    }

    public function testUploadWhenFileParameterIsEmpty()
    {
        $request = Request::create('', Request::METHOD_POST);

        $this->expectException(BadRequestHttpException::class);

        $this->handler->handle($request);
    }

    public function testUploadWhenFileParameterIsInvalid()
    {
        $file = Mockery::mock(UploadedFile::class)
            ->makePartial();
        $file->shouldReceive('isValid')
            ->andReturn(false);

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ]);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->handler->handle($request);
    }

    public function excludedPostParameterProvider()
    {
        return [
            'resumableChunkNumber' => ['resumableChunkNumber'],
            'resumableTotalChunks' => ['resumableTotalChunks'],
            'resumableChunkSize' => ['resumableChunkSize'],
            'resumableTotalSize' => ['resumableTotalSize'],
            'resumableIdentifier' => ['resumableIdentifier'],
            'resumableFilename' => ['resumableFilename'],
            'resumableRelativePath' => ['resumableRelativePath'],
            'resumableCurrentChunkSize' => ['resumableCurrentChunkSize'],
            'resumableType' => ['resumableType'],
        ];
    }

    /**
     * @dataProvider excludedPostParameterProvider
     */
    public function testPostParameterValidation($exclude)
    {
        $arr = [
            'resumableChunkNumber' => 1,
            'resumableTotalChunks' => 2,
            'resumableChunkSize' => 100,
            'resumableTotalSize' => 200,
            'resumableIdentifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'resumableFilename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableRelativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableCurrentChunkSize' => 100,
            'resumableType' => 'text/plain',
        ];

        unset($arr[$exclude]);

        $request = Request::create('', Request::METHOD_POST, $arr, [], [
            'file' => UploadedFile::fake()
                ->create('test.txt', 100),
        ]);

        $this->expectException(ValidationException::class);

        $this->handler->handle($request);
    }

    public function testUploadFirstChunk()
    {
        $request = Request::create('', Request::METHOD_POST, [
            'resumableChunkNumber' => 1,
            'resumableTotalChunks' => 2,
            'resumableChunkSize' => 100,
            'resumableTotalSize' => 200,
            'resumableIdentifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'resumableFilename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableRelativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableCurrentChunkSize' => 100,
            'resumableType' => 'text/plain',
        ], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 50]);

        Storage::disk('local')->assertExists('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt/000-099');

        Event::assertNotDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt.txt';
        });
    }

    public function testUploadFirstChunkWithCallback()
    {
        $request = Request::create('', Request::METHOD_POST, [
            'resumableChunkNumber' => 1,
            'resumableTotalChunks' => 2,
            'resumableChunkSize' => 100,
            'resumableTotalSize' => 200,
            'resumableIdentifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'resumableFilename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableRelativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableCurrentChunkSize' => 100,
            'resumableType' => 'text/plain',
        ], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ]);

        $callback = $this->createClosureMock($this->never());

        $this->handler->handle($request, $callback);

        Event::assertNotDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt.txt';
        });
    }

    public function testUploadLastChunk()
    {
        $this->createFakeLocalFile('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt', '000-099');

        $request = Request::create('', Request::METHOD_POST, [
            'resumableChunkNumber' => 2,
            'resumableTotalChunks' => 2,
            'resumableChunkSize' => 100,
            'resumableTotalSize' => 200,
            'resumableIdentifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'resumableFilename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableRelativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableCurrentChunkSize' => 100,
            'resumableType' => 'text/plain',
        ], [], [
            'file' => UploadedFile::fake()
                ->create('test.txt', 100),
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        Storage::disk('local')->assertExists('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt/100-199');
        Storage::disk('local')->assertExists('merged/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt.txt');

        Event::assertDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt.txt';
        });
    }

    public function testUploadLastChunkWithCallback()
    {
        $this->createFakeLocalFile('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt', '000-099');

        $request = Request::create('', Request::METHOD_POST, [
            'resumableChunkNumber' => 2,
            'resumableTotalChunks' => 2,
            'resumableChunkSize' => 100,
            'resumableTotalSize' => 200,
            'resumableIdentifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'resumableFilename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableRelativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'resumableCurrentChunkSize' => 100,
            'resumableType' => 'text/plain',
        ], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ]);

        $callback = $this->createClosureMock(
            $this->once(),
            'local',
            'merged/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt.txt'
        );

        $this->handler->handle($request, $callback);

        Event::assertDispatched(FileUploaded::class, function ($event) {
            return $event->file = 'merged/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt.txt';
        });
    }
}