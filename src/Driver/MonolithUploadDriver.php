<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Exception\UploadHttpException;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use LaraCrafts\ChunkUploader\Response\MonolithUploadResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class MonolithUploadDriver extends UploadDriver
{
    /**
     * @var string
     */
    private $fileParam;

    public function __construct($config)
    {
        $this->fileParam = $config['param'];
    }

    public function handle(Request $request, Identifier $identifier, array $config): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->handlePost($request, $identifier, $config);
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->handleGet($request, $config);
        }

        if ($request->isMethod(Request::METHOD_DELETE)) {
            return $this->handleDelete($request, $config);
        }

        throw new MethodNotAllowedHttpException([Request::METHOD_POST, Request::METHOD_GET, Request::METHOD_DELETE]);
    }

    public function handlePost(Request $request, Identifier $identifier, array $config): Response
    {
        $file = $request->file($this->fileParam);

        if (! $file->isValid()) {
            throw new UploadHttpException($file->getErrorMessage());
        }

        $filename = $identifier->generateUploadedFileIdentifierName($file);

        $path = $file->storeAs($this->getMergedDirectory($config), $filename, [
            'disk' => $this->getDisk($config),
        ]);

        return new MonolithUploadResponse([], $path);
    }

    public function handleGet(Request $request, array $config): Response
    {
        $filename = $request->query($this->fileParam, $request->route($this->fileParam));

        return $this->fileResponse($filename, $config);
    }

    public function handleDelete(Request $request, array $config)
    {
        $filename = $request->post($this->fileParam, $request->route($this->fileParam));

        $path = $this->getMergedDirectory($config) . '/' . $filename;
        Storage::disk($this->getDisk($config))->delete($path);

        return new Response();
    }
}
