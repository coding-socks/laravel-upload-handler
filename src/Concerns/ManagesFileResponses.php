<?php

namespace LaraCrafts\ChunkUploader\Concerns;

trait ManagesFileResponses
{
    /**
     * @var \Illuminate\Http\File[]
     */
    protected $chunks;

    /**
     * @var \Illuminate\Http\File
     */
    protected $mergedFile;

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->mergedFile !== null;
    }

    /**
     * @return \Illuminate\Http\File|null
     */
    public function getMergedFile()
    {
        return $this->mergedFile;
    }

    /**
     * @return \Illuminate\Http\File[]
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }
}
