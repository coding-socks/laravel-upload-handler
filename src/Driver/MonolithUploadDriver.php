<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Exception\UploadHttpException;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use LaraCrafts\ChunkUploader\Response\MonolithUploadResponse;
use LaraCrafts\ChunkUploader\StorageConfig;
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

    public function handle(Request $request, Identifier $identifier, StorageConfig $config): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->save($request, $identifier, $config);
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->download($request, $config);
        }

        if ($request->isMethod(Request::METHOD_DELETE)) {
            return $this->delete($request, $config);
        }

        throw new MethodNotAllowedHttpException([Request::METHOD_POST, Request::METHOD_GET, Request::METHOD_DELETE]);
    }

    public function save(Request $request, Identifier $identifier, StorageConfig $config): Response
    {
        $file = $request->file($this->fileParam);

        if (! $file->isValid()) {
            throw new UploadHttpException($file->getErrorMessage());
        }

        $filename = $identifier->generateUploadedFileIdentifierName($file);

        $path = $file->storeAs($config->getMergedDirectory(), $filename, [
            'disk' => $config->getDisk(),
        ]);

        return new MonolithUploadResponse([], $path);
    }

    public function download(Request $request, StorageConfig $config): Response
    {
        $filename = $request->query($this->fileParam, $request->route($this->fileParam));

        return $this->fileResponse($filename, $config);
    }

    public function delete(Request $request, StorageConfig $config)
    {
        $filename = $request->post($this->fileParam, $request->route($this->fileParam));

        $path = $config->getMergedDirectory() . '/' . $filename;
        Storage::disk($config->getDisk())->delete($path);

        return new Response();
    }
}
