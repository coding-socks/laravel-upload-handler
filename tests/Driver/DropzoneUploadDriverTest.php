<?php

namespace LaraCrafts\ChunkUploader\Tests\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LaraCrafts\ChunkUploader\Driver\DropzoneUploadDriver;
use LaraCrafts\ChunkUploader\Exception\UploadHttpException;
use LaraCrafts\ChunkUploader\Tests\TestCase;
use LaraCrafts\ChunkUploader\UploadHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DropzoneUploadDriverTest extends TestCase
{
    /**
     * @var UploadHandler
     */
    private $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->app->make('config')->set('chunk-uploader.uploader', 'dropzone');
        $this->app->make('config')->set('chunk-uploader.sweep', false);
        $this->handler = $this->app->make(UploadHandler::class);
    }

    public function testDriverInstance()
    {
        $manager = $this->app->make('chunk-uploader.upload-manager');

        $this->assertInstanceOf(DropzoneUploadDriver::class, $manager->driver());
    }

    public function testFileParameterValidationWhenFileParameterIsEmpty()
    {
        Storage::fake('local');

        $request = Request::create('', Request::METHOD_POST);

        $this->expectException(BadRequestHttpException::class);

        $this->handler->handle($request);
    }

    public function testFileParameterValidationWhenFileParameterIsInvalid()
    {
        Storage::fake('local');

        $file = \Mockery::mock(UploadedFile::class)->makePartial();
        $file->shouldReceive('isValid')
            ->andReturn(false);

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => $file,
        ]);

        $this->expectException(UploadHttpException::class);

        $this->handler->handle($request);
    }

    public function testUploadMonolith()
    {
        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');
        Storage::fake('local');

        $request = Request::create('', Request::METHOD_POST, [], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
        ]);

        /** @var \Illuminate\Foundation\Testing\TestResponse|\LaraCrafts\ChunkUploader\Response\Response $response */
        $response = $this->createTestResponse($this->handler->handle($request));
        $response->assertSuccessful();
        $response->assertJson(['done' => 100]);

        $this->assertEmpty($response->getChunks());
        $this->assertTrue($response->isFinished());
        $this->assertNotNull($response->getMergedFile());

        Storage::disk('local')->assertExists('merged/2494cefe4d234bd331aeb4514fe97d810efba29b.txt');
    }

    public function excludedPostParameterProvider()
    {
        return [
            'dzuuid' => ['dzuuid'],
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
        Storage::fake('local');

        $arr = [
            'dzuuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
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
        Storage::fake('local');

        $request = Request::create('', Request::METHOD_POST, [
            'dzuuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
            'dzchunkindex' => 0,
            'dztotalfilesize' => 200,
            'dzchunksize' => 100,
            'dztotalchunkcount' => 2,
            'dzchunkbyteoffset' => 100,
        ], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
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
        Storage::fake('local');
        $this->createFakeLocalFile('chunks/2494cefe4d234bd331aeb4514fe97d810efba29b.txt', '000');

        $request = Request::create('', Request::METHOD_POST, [
            'dzuuid' => '2494cefe4d234bd331aeb4514fe97d810efba29b',
            'dzchunkindex' => 1,
            'dztotalfilesize' => 200,
            'dzchunksize' => 100,
            'dztotalchunkcount' => 2,
            'dzchunkbyteoffset' => 100,
        ], [], [
            'file' => UploadedFile::fake()->create('test.txt', 100),
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
}
