<?php

namespace LaraCrafts\ChunkUploader\Tests;

use Illuminate\Http\UploadedFile;
use LaraCrafts\ChunkUploader\ChunkUploaderServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ChunkUploaderServiceProvider::class,
        ];
    }

    /**
     * @param $path
     * @param $name
     * @param int $kilobytes
     */
    protected function createFakeLocalFile($path, $name, int $kilobytes = 0)
    {
        $file = UploadedFile::fake()->create($name, $kilobytes);
        $file->storeAs($path, $name, [
            'disk' => 'local',
        ]);
    }
}
