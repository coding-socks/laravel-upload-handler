<?php

namespace CodingSocks\UploadHandler\Range;

use InvalidArgumentException;

class ResumableJsRange extends RequestBodyRange
{
    /**
     * @param string $numberOfChunksKey
     */
    protected function validateNumberOfChunks(string $numberOfChunksKey): void
    {
        if ($this->numberOfChunks <= 0) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', $numberOfChunksKey));
        }
    }

    /**
     * @param string $indexKey
     * @param string $numberOfChunksKey
     */
    protected function validateIndexKey(string $indexKey, string $numberOfChunksKey): void
    {
        if ($this->index < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', $indexKey));
        }
        if ($this->index > $this->numberOfChunks) {
            throw new InvalidArgumentException(sprintf('`%s` must be smaller than or equal to `%s`', $indexKey, $numberOfChunksKey));
        }
    }

    /**
     * @param string $indexKey
     * @param string $numberOfChunksKey
     * @param string $chunkSizeKey
     * @param string $totalSizeKey
     */
    protected function validateTotalSize(string $indexKey, string $numberOfChunksKey, string $chunkSizeKey, string $totalSizeKey): void
    {
        if ($this->totalSize < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', $totalSizeKey));
        } elseif ($this->totalSize <= ($this->index - 1) * $this->chunkSize) {
            throw new InvalidArgumentException(
                sprintf('`%s` must be greater than or equal to the multiple of `%s` and `%s`', $totalSizeKey, $chunkSizeKey, $indexKey)
            );
        } elseif ($this->totalSize > $this->numberOfChunks * $this->chunkSize) {
            throw new InvalidArgumentException(
                sprintf('`%s` must be smaller than or equal to the multiple of `%s` and `%s`', $totalSizeKey, $chunkSizeKey, $numberOfChunksKey)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStart(): float
    {
        return ($this->index - 1) * $this->chunkSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnd(): float
    {
        $end = ($this->index * $this->chunkSize) - 1;

        $sizeIndex = $this->totalSize - 1;
        if ($end > ($sizeIndex)) {
            return $sizeIndex;
        }

        return $end;
    }

    /**
     * {@inheritDoc}
     */
    public function isFirst(): bool
    {
        return $this->index === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function isLast(): bool
    {
        return $this->index === $this->numberOfChunks;
    }
}
