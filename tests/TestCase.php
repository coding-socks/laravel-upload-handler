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
    protected function createFakeLocalFile($path, $name)
    {
        $file = UploadedFile::fake()->create($name);
        $file->storeAs($path, $name, [
            'disk' => 'local',
        ]);
    }
}
