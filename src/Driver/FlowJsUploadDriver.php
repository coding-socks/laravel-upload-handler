<?php

namespace LaraCrafts\ChunkUploader\Driver;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use LaraCrafts\ChunkUploader\Helper\ChunkHelpers;
use LaraCrafts\ChunkUploader\Range\OneBasedRequestBodyRange;
use LaraCrafts\ChunkUploader\Response\PercentageJsonResponse;
use LaraCrafts\ChunkUploader\StorageConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FlowJsUploadDriver extends ResumableJsUploadDriver
{
    /**
     * ResumableJsUploadDriver constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $config['parameter-namespace'] = '';
        $config['parameter-names'] = [
            // The name of the chunk index (base-1) in the current upload POST parameter to use for the file chunk.
            'chunk-number' => 'flowChunkNumber',
            // The name of the total number of chunks POST parameter to use for the file chunk.
            'total-chunks' => 'flowTotalChunks',
            // The name of the general chunk size POST parameter to use for the file chunk.
            'chunk-size' => 'flowChunkSize',
            // The name of the total file size number POST parameter to use for the file chunk.
            'total-size' => 'flowTotalSize',
            // The name of the unique identifier POST parameter to use for the file chunk.
            'identifier' => 'flowIdentifier',
            // The name of the original file name POST parameter to use for the file chunk.
            'file-name' => 'flowFilename',
            // The name of the file's relative path POST parameter to use for the file chunk.
            'relative-path' => 'flowRelativePath',
            // The name of the current chunk size POST parameter to use for the file chunk.
            'current-chunk-size' => 'flowCurrentChunkSize',
        ];
        parent::__construct($config);
    }
}
