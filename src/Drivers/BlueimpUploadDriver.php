<?php

namespace LaraCrafts\ChunkUploader\Drivers;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use LaraCrafts\ChunkUploader\Ranges\ContentRange;
use LaraCrafts\ChunkUploader\Identifiers\Identifier;
use LaraCrafts\ChunkUploader\Concerns\InteractsWithChunks;
use LaraCrafts\ChunkUploader\Exceptions\UploadHttpException;
use LaraCrafts\ChunkUploader\Responses\PercentageJsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class BlueimpUploadDriver extends UploadDriver
{
    use InteractsWithChunks;

    /**
     * @var string
     */
    private $fileParam;

    public function __construct($config)
    {
        $this->fileParam = $config['param'];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \HttpHeaderException
     */
    public function handle(Request $request, Identifier $identifier, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        if ($this->isRequestMethodIn($request, [Request::METHOD_HEAD, Request::METHOD_OPTIONS])) {
            return $this->info();
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_GET])) {
            return $this->download($request, $config);
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH])) {
            return $this->save($request, $identifier, $config, $fileUploaded);
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_DELETE])) {
            return $this->delete($request, $config);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_HEAD,
            Request::METHOD_OPTIONS,
            Request::METHOD_GET,
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_PATCH,
            Request::METHOD_DELETE,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function info(): Response
    {
        return new JsonResponse([], Response::HTTP_OK, [
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Content-Disposition' => 'inline; filename="files.json"',
            'X-Content-Type-Options' => 'nosniff',
            'Vary' => 'Accept',
        ]);
    }

    public function download(Request $request, StorageConfig $config)
    {
        $download = $request->query('download', false);
        if ($download !== false) {
            $filename = $request->query($this->fileParam);

            return $this->fileResponse($filename, $config);
        }

        $filename = $request->query($this->fileParam);
        $directory = $config->getChunkDirectory() . '/' . $filename;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($config->getDisk());

        if (! $disk->exists($directory)) {
            return new JsonResponse([
                'file' => null,
            ]);
        }

        $chunk = Arr::last($disk->files($directory));
        $size = explode('-', basename($chunk))[1] + 1;

        return new JsonResponse([
            'file' => [
                'name' => $filename,
                'size' => $size,
            ],
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \LaraCrafts\ChunkUploader\Identifiers\Identifier $identifier
     * @param StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \HttpHeaderException
     */
    public function save(Request $request, Identifier $identifier, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        $range = new ContentRange($request->headers);

        $file = $request->file($this->fileParam);

        if (null === $file) {
            $file = Arr::first($request->file(Str::plural($this->fileParam), []));
        }

        if (null === $file) {
            throw new BadRequestHttpException('File not found in request body');
        }

        if (! $file->isValid()) {
            throw new UploadHttpException($file->getErrorMessage());
        }

        $filename = $identifier->generateUploadedFileIdentifierName($file);

        $chunks = $this->storeChunk($config, $range, $file, $filename);

        if (! $range->isLast()) {
            return new PercentageJsonResponse($range->getPercentage());
        }

        $path = $this->mergeChunks($config, $chunks, $filename);

        if (! empty($config->sweep())) {
            Storage::disk($config->getDisk())->deleteDirectory($filename);
        }

        $this->triggerFileUploadedEvent($config->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }

    /**
     * @param Request $request
     * @param StorageConfig $config
     * @return Response
     */
    public function delete(Request $request, StorageConfig $config)
    {
        $filename = $request->post($this->fileParam);

        $path = $config->getMergedDirectory() . '/' . $filename;
        Storage::disk($config->getDisk())->delete($path);

        return new Response();
    }
}
