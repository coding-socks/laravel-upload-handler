<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Response\PercentageJsonResponse;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class MonolithUploadDriver extends UploadDriver
{
    /**
     * @var string
     */
    private $fileParam;

    /**
     * MonolithUploadDriver constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->fileParam = $config['param'];
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->save($request, $config, $fileUploaded);
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->download($request, $config);
        }

        if ($request->isMethod(Request::METHOD_DELETE)) {
            return $this->delete($request, $config);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_POST,
            Request::METHOD_GET,
            Request::METHOD_DELETE,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save(Request $request, StorageConfig $config, Closure $fileUploaded = null): Response
    {
        $file = $request->file($this->fileParam);

        $this->validateUploadedFile($file);

        $path = $file->store($config->getMergedDirectory(), [
            'disk' => $config->getDisk(),
        ]);

        $this->triggerFileUploadedEvent($config->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Request $request, StorageConfig $config): Response
    {
        $filename = $request->query($this->fileParam, $request->route($this->fileParam));

        return $this->fileResponse($filename, $config);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \LaraCrafts\ChunkUploader\StorageConfig $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request, StorageConfig $config): Response
    {
        $filename = $request->post($this->fileParam, $request->route($this->fileParam));

        $path = $config->getMergedDirectory() . '/' . $filename;
        Storage::disk($config->getDisk())->delete($path);

        return new Response();
    }
}
