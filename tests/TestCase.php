<?php

namespace LaraCrafts\ChunkUploader\Tests;

use Illuminate\Http\UploadedFile;
use LaraCrafts\ChunkUploader\ChunkUploaderServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            ChunkUploaderServiceProvider::class,
        ];
    }

    /**
     * @param $path
     * @param $name
     */
    protected function createFakeLocalFile($path, $name)
    {
        $file = UploadedFile::fake()->create($name);
        $file->storeAs($path, $name, [
            'disk' => 'local',
        ]);
    }

    /**
     * https://github.com/sebastianbergmann/phpunit-mock-objects/issues/257
     *
     * @param $expects
     * @param mixed ...$arguments
     *
     * @return \Closure
     */
    protected function createClosureMock($expects, ...$arguments)
    {
        /** @var \Closure|\PHPUnit\Framework\MockObject\MockObject $callback */
        $callback = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $callback->expects($expects)
            ->method('__invoke')
            ->with(...$arguments);

        return function () use ($callback) {
            return $callback(...func_get_args());
        };
    }
}
