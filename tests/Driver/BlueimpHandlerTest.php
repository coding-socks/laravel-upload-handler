<?php

namespace CodingSocks\UploadHandler\Tests\Driver;

use CodingSocks\UploadHandler\Driver\BlueimpHandler;
use CodingSocks\UploadHandler\Event\FileUploaded;
use CodingSocks\UploadHandler\Exception\InternalServerErrorHttpException;
use CodingSocks\UploadHandler\Tests\TestCase;
use CodingSocks\UploadHandler\UploadHandler;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class BlueimpHandlerTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('upload-handler.identifier', 'nop');
        config()->set('upload-handler.handler', 'blueimp');
        config()->set('upload-handler.sweep', false);
        $this->handler = app()->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = app()->make('upload-handler.upload-manager');

        $this->assertInstanceOf(BlueimpHandler::class, $manager->driver());
    }

    public function notAllowedRequestMethods()
    {
        return [
            'DELETE' => [Request::METHOD_DELETE],
            'PURGE' => [Request::METHOD_PURGE],
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

    public function testInfo()
    {
        $request = Request::create('', Request::METHOD_HEAD);

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
        $response->assertSuccessful();

        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Content-Disposition', 'inline; filename="files.json"');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Vary', 'Accept');
    }

    public function testResume()
    {
        $this->createFakeLocalFile('chunks/200_test.txt', '000-099');

        $request = Request::create('', Request::METHOD_GET, [
            'file' => 'test.txt',
            'totalSize' => '200',
        ]);

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
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
        $file = new UploadedFile('', '', null, \UPLOAD_ERR_INI_SIZE);

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

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
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

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
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
}
