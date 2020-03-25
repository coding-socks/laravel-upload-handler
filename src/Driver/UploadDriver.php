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
     * @param Request $request
     * @param StorageConfig $storageConfig
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    abstract public function handle(Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response;

    /**
     * @param string $filename
     * @param StorageConfig $storageConfig
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

    public function isRequestMethodIn(Request $request, array $methods): bool
    {
        foreach ($methods as $method) {
            if ($request->isMethod($method)) {
                return true;
            }
        }

        return false;
    }

    protected function triggerFileUploadedEvent($disk, $path, Closure $fileUploaded = null)
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
    protected function validateUploadedFile(UploadedFile $file = null)
    {
        if (null === $file) {
            throw new BadRequestHttpException('File not found in request body');
        }

        if (! $file->isValid()) {
            throw new InternalServerErrorHttpException($file->getErrorMessage());
        }
    }
}
