<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaraCrafts\ChunkUploader\ContentRange;
use LaraCrafts\ChunkUploader\Exception\UploadHttpException;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use LaraCrafts\ChunkUploader\Response\BlueimpInfoResponse;
use LaraCrafts\ChunkUploader\Response\BlueimpUploadResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class BlueimpUploadDriver extends UploadDriver
{
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
     * @param array $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \HttpHeaderException
     */
    public function handle(Request $request, Identifier $identifier, array $config): Response
    {
        if ($this->isRequestMethodIn($request, [Request::METHOD_HEAD, Request::METHOD_OPTIONS])) {
            return $this->handleHead();
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_GET])) {
            return $this->handleGet($request, $identifier, $config);
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH])) {
            return $this->handlePost($request, $identifier, $config);
        }

        if ($this->isRequestMethodIn($request, [Request::METHOD_DELETE])) {
            return $this->handleDelete($request, $config);
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
    public function handleHead(): Response
    {
        return new BlueimpInfoResponse();
    }

    public function handleGet(Request $request, Identifier $identifier, array $config)
    {
        $download = $request->query('download', false);
        if ($download !== false) {
            $filename = $request->query($this->fileParam);

            return $this->fileResponse($filename, $config);
        }

        $filename = $request->query($this->fileParam);
        $directory = $this->getChunkDirectory($config).'/'.$filename;
        $disk = Storage::disk($this->getDisk($config));

        if (!$disk->exists($directory)) {
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
     * @param array $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \HttpHeaderException
     */
    public function handlePost(Request $request, Identifier $identifier, array $config): Response
    {
        $contentRange = new ContentRange($request->headers);

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

        $len = strlen($contentRange->getTotal());
        $chunkname = implode('-', [
            str_pad($contentRange->getStart(), $len, '0', STR_PAD_LEFT),
            str_pad($contentRange->getEnd(), $len, '0', STR_PAD_LEFT),
        ]);

        $directory = $this->getChunkDirectory($config).'/'.$filename;
        $file->storeAs($directory, $chunkname, [
            'disk' => $this->getDisk($config),
        ]);

        $chunks = Storage::disk($this->getDisk($config))->files($directory);

        if (! $contentRange->isLast()) {
            return new BlueimpUploadResponse($contentRange->getPercentage(), $chunks);
        }

        $path = $this->mergeChunks($config, $chunks, $filename);

        if (! empty($this->sweep($config))) {
            Storage::disk($this->getDisk($config))->deleteDirectory($filename);
            $chunks = [];
        }

        return new BlueimpUploadResponse(100, $chunks, $path);
    }

    public function handleDelete(Request $request, array $config)
    {
        $filename = $request->post($this->fileParam);

        $path = $this->getMergedDirectory($config).'/'.$filename;
        Storage::disk($this->getDisk($config))->delete($path);

        return new Response();
    }
}
