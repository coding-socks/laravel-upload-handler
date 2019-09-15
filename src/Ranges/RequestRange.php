<?php

namespace LaraCrafts\ChunkUploader\Ranges;

use LaraCrafts\ChunkUploader\Contracts\Range as RangeContract;

class RequestRange implements RangeContract
{
    private $index;

    private $numberOfChunks;

    private $totalSize;

    private $chunkSize;

    /**
     * RequestRange constructor.
     *
     * @param int $index The index of the current chunk
     * @param int $numberOfChunks The total number of the chunks
     * @param int $chunkSize The size of the chunk
     * @param int $totalSize The size of the original file
     */
    public function __construct($index, $numberOfChunks, $chunkSize, $totalSize)
    {
        $this->index = $index;
        $this->numberOfChunks = $numberOfChunks;
        $this->totalSize = $totalSize;
        $this->chunkSize = $chunkSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getStart(): float
    {
        return $this->index * $this->chunkSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnd(): float
    {
        $end = (($this->index + 1) * $this->chunkSize) - 1;

        if ($end > ($this->totalSize - 1)) {
            return $this->totalSize - 1;
        }

        return $end;
    }

    /**
     * {@inheritDoc}
     */
    public function getTotal(): float
    {
        return $this->totalSize;
    }

    /**
     * {@inheritDoc}
     */
    public function isFirst(): bool
    {
        return $this->index === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isLast(): bool
    {
        return $this->index === $this->numberOfChunks - 1;
    }
}
