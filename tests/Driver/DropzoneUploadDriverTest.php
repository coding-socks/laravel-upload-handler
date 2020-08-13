<?php

namespace CodingSocks\ChunkUploader\Tests\Driver;

use CodingSocks\ChunkUploader\Driver\DropzoneUploadDriver;
use CodingSocks\ChunkUploader\Event\FileUploaded;
use CodingSocks\ChunkUploader\Exception\InternalServerErrorHttpException;
use CodingSocks\ChunkUploader\Tests\TestCase;
use CodingSocks\ChunkUploader\UploadHandler;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DropzoneUploadDriverTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('config')->set('chunk-uploader.uploader', 'dropzone');
        $this->app->make('config')->set('chunk-uploader.sweep', false);
        $this->handler = $this->app->make(UploadHandler::class);

        Storage::fake('local');
        Event::fake();
    }

    public function testDriverInstance()
    {
        $manager = $this->app->make('chunk-uploader.upload-manager');

        $this->assertInstanceOf(DropzoneUploadDriver::class, $manager->driver());
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

    public function testUploadMonolith()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        Storage::disk('local')->assertExists($file->hashName('merged'));

        Event::assertDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadMonolithWithCallback()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
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

    public function excludedPostParameterProvider()
    {
        return [
            'dzuid' => ['dzuid'],
            'dzchunkindex' => ['dzchunkindex'],
            'dztotalfilesize' => ['dztotalfilesize'],
            'dzchunksize' => ['dzchunksize'],
            'dztotalchunkcount' => ['dztotalchunkcount'],
            'dzchunkbyteoffset' => ['dzchunkbyteoffset'],
        ];
    }

    /**
     * @dataProvider excludedPostParameterProvider
     */
    public function testPostParameterValidation($exclude)
    {
        $arr = [
            'dzuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
            'dzchunkindex' => 0,
            'dztotalfilesize' => 200,
            'dzchunksize' => 100,
            'dztotalchunkcount' => 2,
            'dzchunkbyteoffset' => 100,
        ];

        unset($arr[$exclude]);

        $request = Request::create('', Request::METHOD_POST, $arr, [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ]);

        $this->expectException(ValidationException::class);

        $this->handler->handle($request);
    }

    public function testUploadFirstChunk()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [
            'dzuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
            'dzchunkindex' => 0,
            'dztotalfilesize' => 200,
            'dzchunksize' => 100,
            'dztotalchunkcount' => 2,
            'dzchunkbyteoffset' => 100,
        ], [], [
            'file' => $file,
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 50]);

        Storage::disk('local')->assertExists('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b/000-099');

        Event::assertNotDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadFirstChunkWithCallback()
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [
            'dzuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
            'dzchunkindex' => 0,
            'dztotalfilesize' => 200,
            'dzchunksize' => 100,
            'dztotalchunkcount' => 2,
            'dzchunkbyteoffset' => 100,
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
        $this->createFakeLocalFile('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b', '000');

        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [
            'dzuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
            'dzchunkindex' => 1,
            'dztotalfilesize' => 200,
            'dzchunksize' => 100,
            'dztotalchunkcount' => 2,
            'dzchunkbyteoffset' => 100,
        ], [], [
            'file' => $file,
        ]);

        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        Storage::disk('local')->assertExists('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b/100-199');
        Storage::disk('local')->assertExists($file->hashName('merged'));

        Event::assertDispatched(FileUploaded::class, function ($event) use ($file) {
            return $event->file = $file->hashName('merged');
        });
    }

    public function testUploadLastChunkWithCallback()
    {
        $this->createFakeLocalFile('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b', '000');

        $file = UploadedFile::fake()->create('test.txt', 100);
        $request = Request::create('', Request::METHOD_POST, [
            'dzuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
            'dzchunkindex' => 1,
            'dztotalfilesize' => 200,
            'dzchunksize' => 100,
            'dztotalchunkcount' => 2,
            'dzchunkbyteoffset' => 100,
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
