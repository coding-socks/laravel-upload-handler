<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Event\FileUploaded;
use LaraCrafts\ChunkUploader\Exception\InternalServerErrorHttpException;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class UploadDriver
{

    /**
     * @param \Illuminate\Http\Request $request
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    abstract public function handle(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response;

    /**
     * @param string $filename
     * @param \LaraCrafts\ChunkUploader\StorageConfig $storageConfig
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fileResponse(string $filename, StorageConfig $storageConfig): Response
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($storageConfig->getDisk());
        $prefix = $storageConfig->getMergedDirectory() . '/';

        if (! $disk->exists($prefix . $filename)) {
            throw new NotFoundHttpException($filename . ' file not found on server');
        }

        $path = $disk->path($prefix . $filename);

        return new BinaryFileResponse($path, 200, [], true, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    /**
     * Check if the request type of the given request is in the specified list.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $methods
     *
     * @return bool
     */
    public function isRequestMethodIn(Request $request, array $methods): bool
    {
        foreach ($methods as $method) {
            if ($request->isMethod($method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch a {@link \LaraCrafts\ChunkUploader\Event\FileUploaded} event.
     * Also call the given {@link \Closure} if not null.
     *
     * @param $disk
     * @param $path
     * @param \Closure|null $fileUploaded
     */
    protected function triggerFileUploadedEvent($disk, $path, Closure $fileUploaded = null): void
    {
        if ($fileUploaded !== null) {
            $fileUploaded($disk, $path);
        }

        event(new FileUploaded($disk, $path));
    }

    /**
     * Validate an uploaded file. An exception is thrown when it is invalid.
     *
     * @param \Illuminate\Http\UploadedFile|null $file
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException when given file is null.
     * @throws \LaraCrafts\ChunkUploader\Exception\InternalServerErrorHttpException when given file is invalid.
     */
    protected function validateUploadedFile(UploadedFile $file = null): void
    {
        if (null === $file) {
            throw new BadRequestHttpException('File not found in request body');
        }

        if (! $file->isValid()) {
            throw new InternalServerErrorHttpException($file->getErrorMessage());
        }
    }
}
