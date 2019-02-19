<?php

namespace LaraCrafts\ChunkUploader\Response;

trait Response
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
    public function getMergedFile(): ?string
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
