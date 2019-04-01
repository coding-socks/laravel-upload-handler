<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaraCrafts\ChunkUploader\Identifier\Identifier;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class UploadDriver
{
    /**
     * @var int
     */
    protected $bufferSize = 4096;

    /**
     * @param \Illuminate\Http\Request $request
     * @param Identifier $identifier
     * @param array $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    abstract public function handle(Request $request, Identifier $identifier, array $config): Response;

    /**
     * @param string $filename
     * @param array $config
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fileResponse(string $filename, array $config): Response
    {
        $disk = Storage::disk($this->getDisk($config));
        $prefix = $this->getMergedDirectory($config) . '/';

        if (! $disk->exists($prefix . $filename)) {
            throw new NotFoundHttpException($filename . ' file not found on server');
        }

        $path = $disk->path($prefix . $filename);

        return new BinaryFileResponse($path);
    }

    /**
     * @param array $config
     * @param array $chunks
     * @param string $targetFilename
     *
     * @return string
     */
    public function mergeChunks(array $config, array $chunks, string $targetFilename): string
    {
        $disk = Storage::disk($this->getDisk($config));

        $mergedDirectory = $this->getMergedDirectory($config);
        $disk->makeDirectory($mergedDirectory);
        $targetPath = $mergedDirectory . '/' . $targetFilename;

        $chunk = array_shift($chunks);
        $disk->copy($chunk, $targetPath);
        $mergedFile = new File($disk->path($targetPath));
        $mergedFileInfo = $mergedFile->openFile('ab+');

        foreach ($chunks as $chunk) {
            $chunkFileInfo = (new File($disk->path($chunk)))->openFile('rb');

            while (! $chunkFileInfo->eof()) {
                $mergedFileInfo->fwrite($chunkFileInfo->fread($this->bufferSize));
            }
        }

        return $targetPath;
    }

    public function getDisk(array $config): string
    {
        return $config['disk'];
    }

    public function getChunkDirectory(array $config): string
    {
        return $config['directories']['chunk'];
    }

    public function getMergedDirectory(array $config): string
    {
        return $config['directories']['merged'];
    }

    public function sweep(array $config): bool
    {
        return $config['sweep'];
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
}
