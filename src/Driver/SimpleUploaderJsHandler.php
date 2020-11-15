<?php

namespace CodingSocks\UploadHandler\Driver;

use CodingSocks\UploadHandler\Identifier\Identifier;

class SimpleUploaderJsHandler extends ResumableJsHandler
{
    public function __construct($config, Identifier $identifier)
    {
        $config['parameter-namespace'] = '';
        $config['parameter-names'] = [
            // The name of the chunk index (base-1) in the current upload POST parameter to use for the file chunk.
            'chunk-number' => 'chunkNumber',
            // The name of the total number of chunks POST parameter to use for the file chunk.
            'total-chunks' => 'totalChunks',
            // The name of the general chunk size POST parameter to use for the file chunk.
            'chunk-size' => 'chunkSize',
            // The name of the total file size number POST parameter to use for the file chunk.
            'total-size' => 'totalSize',
            // The name of the unique identifier POST parameter to use for the file chunk.
            'identifier' => 'identifier',
            // The name of the original file name POST parameter to use for the file chunk.
            'file-name' => 'filename',
            // The name of the file's relative path POST parameter to use for the file chunk.
            'relative-path' => 'relativePath',
            // The name of the current chunk size POST parameter to use for the file chunk.
            'current-chunk-size' => 'currentChunkSize',
        ];
        parent::__construct($config, $identifier);
    }
}
