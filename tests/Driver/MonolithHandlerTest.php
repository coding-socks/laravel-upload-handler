<?php

namespace CodingSocks\UploadHandler\Tests\Driver;

use CodingSocks\UploadHandler\Driver\MonolithHandler;
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

class MonolithHandlerTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('upload-handler.handler', 'monolith');
        $this->handler = app()->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = app()->make('upload-handler.upload-manager');

        $this->assertInstanceOf(MonolithHandler::class, $manager->driver());
    }

    public function notAllowedRequestMethods()
    {
        return [
            'HEAD' => [Request::METHOD_HEAD],
            'GET' => [Request::METHOD_GET],
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

    public function testUpload()
    {
        $file = UploadedFile::fake()->create('test.txt', 20);
        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ]);

        $response = TestResponse::fromBaseResponse($this->handler->handle($request));
        $response->assertSuccessful();

        Storage::disk('local')->assertExists($file->hashName('merged'));

        Event::assertDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadWithCallback()
    {
        $file = UploadedFile::fake()->create('test.txt', 20);
        $request = Request::create('', Request::METHOD_POST, [], [], [
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
