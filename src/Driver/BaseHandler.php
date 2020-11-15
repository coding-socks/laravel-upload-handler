<?php

namespace CodingSocks\UploadHandler\Driver;

use Closure;
use CodingSocks\UploadHandler\Event\FileUploaded;
use CodingSocks\UploadHandler\Exception\InternalServerErrorHttpException;
use CodingSocks\UploadHandler\StorageConfig;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

abstract class BaseHandler
{

    /**
     * @param \Illuminate\Http\Request $request
     * @param \CodingSocks\UploadHandler\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    abstract public function handle(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response;

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
     * Dispatch a {@link \CodingSocks\UploadHandler\Event\FileUploaded} event.
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
     * @param \Illuminate\Http\UploadedFile|array|null $file
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException when given file is null.
     * @throws \CodingSocks\UploadHandler\Exception\InternalServerErrorHttpException when given file is invalid.
     */
    protected function validateUploadedFile($file): void
    {
        if (null === $file) {
            throw new BadRequestHttpException('File not found in request body');
        }

        if (is_array($file)) {
            throw new UnprocessableEntityHttpException('File parameter cannot be an array');
        }

        if (! $file->isValid()) {
            throw new InternalServerErrorHttpException($file->getErrorMessage());
        }
    }
}
