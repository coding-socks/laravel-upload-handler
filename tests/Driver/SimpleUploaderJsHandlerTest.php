<?php

namespace CodingSocks\UploadHandler\Tests\Driver;

use CodingSocks\UploadHandler\Driver\SimpleUploaderJsHandler;
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

class SimpleUploaderJsHandlerTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('upload-handler.identifier', 'nop');
        config()->set('upload-handler.handler', 'simple-uploader-js');
        config()->set('upload-handler.sweep', false);
        $this->handler = app()->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = app()->make('upload-handler.upload-manager');

        $this->assertInstanceOf(SimpleUploaderJsHandler::class, $manager->driver());
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
            'chunkNumber' => 2,
            'totalChunks' => 2,
            'chunkSize' => 100,
            'totalSize' => 200,
            'identifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'filename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'relativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'currentChunkSize' => 100,
        ]);

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function testResume()
    {
        $this->createFakeLocalFile('chunks/200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt', '000-099');

        $request = Request::create('', Request::METHOD_GET, [
            'chunkNumber' => 1,
            'totalChunks' => 2,
            'chunkSize' => 100,
            'totalSize' => 200,
            'identifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'filename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'relativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'currentChunkSize' => 100,
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
            'chunkNumber' => ['chunkNumber'],
            'totalChunks' => ['totalChunks'],
            'chunkSize' => ['chunkSize'],
            'totalSize' => ['totalSize'],
            'identifier' => ['identifier'],
            'filename' => ['filename'],
            'relativePath' => ['relativePath'],
            'currentChunkSize' => ['currentChunkSize'],
        ];
    }

    /**
     * @dataProvider excludedPostParameterProvider
     */
    public function testPostParameterValidation($exclude)
    {
        $arr = [
            'chunkNumber' => 1,
            'totalChunks' => 2,
            'chunkSize' => 100,
            'totalSize' => 200,
            'identifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'filename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'relativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'currentChunkSize' => 100,
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
            'chunkNumber' => 1,
            'totalChunks' => 2,
            'chunkSize' => 100,
            'totalSize' => 200,
            'identifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'filename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'relativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'currentChunkSize' => 100,
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
            'chunkNumber' => 1,
            'totalChunks' => 2,
            'chunkSize' => 100,
            'totalSize' => 200,
            'identifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'filename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'relativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'currentChunkSize' => 100,
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
            'chunkNumber' => 2,
            'totalChunks' => 2,
            'chunkSize' => 100,
            'totalSize' => 200,
            'identifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'filename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'relativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'currentChunkSize' => 100,
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
            'chunkNumber' => 2,
            'totalChunks' => 2,
            'chunkSize' => 100,
            'totalSize' => 200,
            'identifier' => '200-0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zftxt',
            'filename' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'relativePath' => '0jWZTB1ZDfRQU6VTcXy0mJnL9xKMeEz3HoSPU0Zf.txt',
            'currentChunkSize' => 100,
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
