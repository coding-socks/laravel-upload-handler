<?php

namespace LaraCrafts\ChunkUploader\Response;

class MonolithUploadResponse extends \Illuminate\Http\Response
{
    use Response;

    /**
     * ChunkedFile constructor.
     *
     * @param string[] $chunks
     * @param string $mergedFile
     */
    public function __construct(array $chunks, ?string $mergedFile = null)
    {
        parent::__construct();

        $this->chunks = $chunks;
        $this->mergedFile = $mergedFile;
    }
}
