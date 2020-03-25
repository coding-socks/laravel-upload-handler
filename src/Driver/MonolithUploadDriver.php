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
    public function handle(Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->save($request, $storageConfig, $fileUploaded);
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->download($request, $storageConfig);
        }

        if ($request->isMethod(Request::METHOD_DELETE)) {
            return $this->delete($request, $storageConfig);
        }

        throw new MethodNotAllowedHttpException([
            Request::METHOD_POST,
            Request::METHOD_GET,
            Request::METHOD_DELETE,
        ]);
    }

    /**
     * @param Request $request
     * @param StorageConfig $storageConfig
     * @param \Closure|null $fileUploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save(Request $request, StorageConfig $storageConfig, Closure $fileUploaded = null): Response
    {
        $file = $request->file($this->fileParam);

        $this->validateUploadedFile($file);

        $path = $file->store($storageConfig->getMergedDirectory(), [
            'disk' => $storageConfig->getDisk(),
        ]);

        $this->triggerFileUploadedEvent($storageConfig->getDisk(), $path, $fileUploaded);

        return new PercentageJsonResponse(100);
    }

    /**
     * @param Request $request
     * @param StorageConfig $storageConfig
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Request $request, StorageConfig $storageConfig): Response
    {
        $filename = $request->query($this->fileParam, $request->route($this->fileParam));

        return $this->fileResponse($filename, $storageConfig);
    }

    /**
     * @param Request $request
     * @param StorageConfig $storageConfig
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request, StorageConfig $storageConfig)
    {
        $filename = $request->post($this->fileParam, $request->route($this->fileParam));

        $path = $storageConfig->getMergedDirectory() . '/' . $filename;
        Storage::disk($storageConfig->getDisk())->delete($path);

        return new Response();
    }
}
