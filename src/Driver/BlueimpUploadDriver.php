<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaraCrafts\ChunkUploader\Exception\UploadHttpException;
use LaraCrafts\ChunkUploader\Helper\ChunkHelpers;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use LaraCrafts\ChunkUploader\Range\ContentRange;
use LaraCrafts\ChunkUploader\Response\BlueimpInfoResponse;
use LaraCrafts\ChunkUploader\Response\PercentageJsonResponse;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class BlueimpUploadDriver extends UploadDriver
{
    use ChunkHelpers;

    /**
     * @var string
     */
    private $fileParam;

    public function __construct($config)
    {
        $this->fileParam = $config['param'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \LaraCrafts\ChunkUploader\Identifier\Identifier $identifier
     * @param StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \HttpHeaderException
     */
    public function handle(Request $request, Identifier $identifier, StorageConfig $config): Response
    {
        if ($this->isRequestMethodIn($request, [Request::METHOD_HEAD, Request::METHOD_OPTIONS])) {
            return $this->info();
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_GET])) {
            return $this->download($request, $config);
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH])) {
            return $this->save($request, $identifier, $config);
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
        return new BlueimpInfoResponse();
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
     * @param \LaraCrafts\ChunkUploader\Identifier\Identifier $identifier
     * @param StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \HttpHeaderException
     */
    public function save(Request $request, Identifier $identifier, StorageConfig $config): Response
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
            return new PercentageJsonResponse($range->getPercentage(), $chunks);
        }

        $path = $this->mergeChunks($config, $chunks, $filename);

        if (! empty($config->sweep())) {
            Storage::disk($config->getDisk())->deleteDirectory($filename);
            $chunks = [];
        }

        return new PercentageJsonResponse(100, $chunks, $path);
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
