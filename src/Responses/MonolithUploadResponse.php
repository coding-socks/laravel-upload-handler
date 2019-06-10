<?php

namespace LaraCrafts\ChunkUploader\Responses;

use LaraCrafts\ChunkUploader\Concerns\ManagesFileResponses;

class MonolithUploadResponse extends \Illuminate\Http\Response
{
    use ManagesFileResponses;

    /**
     * ChunkedFile constructor.
     *
     * @param string[] $chunks
     * @param string $mergedFile
     */
    public function __construct(array $chunks, string $mergedFile = null)
    {
        parent::__construct();

        $this->chunks = $chunks;
        $this->mergedFile = $mergedFile;
    }
}
