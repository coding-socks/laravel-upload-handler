<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class UploadDriver
{

    /**
     * @param \Illuminate\Http\Request $request
     * @param Identifier $identifier
     * @param StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    abstract public function handle(Request $request, Identifier $identifier, StorageConfig $config): Response;

    /**
     * @param string $filename
     * @param array $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fileResponse(string $filename, StorageConfig $config): Response
    {
        $disk = Storage::disk($config->getDisk());
        $prefix = $config->getMergedDirectory() . '/';

        if (! $disk->exists($prefix . $filename)) {
            throw new NotFoundHttpException($filename . ' file not found on server');
        }

        $path = $disk->path($prefix . $filename);

        return new BinaryFileResponse($path);
    }

    public function isRequestMethodIn(Request $request, array $methods): bool
    {
        foreach ($methods as $method) {
            if ($request->isMethod($method)) {
                return true;
            }
        }

        return false;
    }
}
