<?php

namespace CodingSocks\UploadHandler\Tests\Driver;

use CodingSocks\UploadHandler\Driver\ResumableJsHandler;
use CodingSocks\UploadHandler\Event\FileUploaded;
use CodingSocks\UploadHandler\Exception\InternalServerErrorHttpException;
use CodingSocks\UploadHandler\Tests\TestCase;
use CodingSocks\UploadHandler\UploadHandler;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ResumableJsHandlerTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('upload-handler.identifier', 'nop');
        config()->set('upload-handler.handler', 'resumable-js');
        config()->set('upload-handler.sweep', false);
        $this->handler = app()->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = app()->make('upload-handler.upload-manager');

        $this->assertInstanceOf(ResumableJsHandler::class, $manager->driver());
    }

    public function notAllowedRequestMethods()
    {
        return [
            'HEAD' => [Request::METHOD_HEAD],
            'PUT' => [Request::METHOD_PUT],
            'PATCH' => [Request::METHOD_PATCH],
            'DELETE' => [Request::METHOD_DELETE],
            'PURGE' => [Request::METHOD_PURGE],
            'OPTIONS' => [Request::METHOD_OPTIONS],
            'TRACE' => [Request::METHOD_TRACE],
            'CONNECT' => [Request::METHOD_CONNECT],
        ];
    }

    /**
     * @dataProvider notAllowedRequestMethods
     */
    public function testMethodNotAllowed($requestMethod)
    {
        $request = Request::create('', $requestMethod);

        $this->expectException(MethodNotAllowedHttpException::class);

        TestResponse::fromBaseResponse($this->handler->handle($request));
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

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
        $response->assertStatus(Response::HTTP_NO_CONTENT);
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

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
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
        $file = new UploadedFile('', '', null, \UPLOAD_ERR_INI_SIZE);

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
        $file = UploadedFile::fake()->create('test.txt', 100);
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
            'file' => $file,
        ]);

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 50]);

        Storage::disk('local')->assertExists('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt/000-099');

        Event::assertNotDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadFirstChunkWithCallback()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
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
            'file' => $file,
        ]);

        $callback = $this->createClosureMock($this->never());

        $this->handler->handle($request, $callback);

        Event::assertNotDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadLastChunk()
    {
        $this->createFakeLocalFile('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt', '000-099');

        $file = UploadedFile::fake()->create('test.txt', 100);
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
            'file' => $file,
        ]);

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        Storage::disk('local')->assertExists('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt/100-199');
        Storage::disk('local')->assertExists($file->hashName('merged'));

        Event::assertDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadLastChunkWithCallback()
    {
        $this->createFakeLocalFile('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt', '000-099');

        $file = UploadedFile::fake()->create('test.txt', 100);
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
            'file' => $file,
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
}
