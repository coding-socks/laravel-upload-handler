<?php

namespace LaraCrafts\ChunkUploader\Range;

use Illuminate\Http\Request;
use InvalidArgumentException;

class RequestBodyRange implements Range
{
    private $index;

    private $numberOfChunks;

    private $totalSize;

    private $chunkSize;

    /**
     * RequestRange constructor.
     *
     * @param \Illuminate\Http\Request|\Symfony\Component\HttpFoundation\ParameterBag $request
     * @param string $indexKey The index of the current chunk
     * @param string $numberOfChunksKey The total number of the chunks
     * @param string $chunkSizeKey The size of the chunk
     * @param string $totalSizeKey The size of the original file
     */
    public function __construct($request, string $indexKey, string $numberOfChunksKey, string $chunkSizeKey, string $totalSizeKey)
    {
        if ($request instanceof Request) {
            $request = $request->request;
        }

        $this->index = $request->get($indexKey);
        $this->numberOfChunks = $request->get($numberOfChunksKey);
        $this->chunkSize = $request->get($chunkSizeKey);
        $this->totalSize = $request->get($totalSizeKey);

        if ($this->numberOfChunks <= 0) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than to zero', $numberOfChunksKey));
        }
        if ($this->index < 0) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than or equal to zero', $indexKey));
        }
        if ($this->index >= $this->numberOfChunks) {
            throw new InvalidArgumentException(sprintf('`%s` must be smaller than `%s`', $indexKey, $numberOfChunksKey));
        }
        if ($this->chunkSize < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', $chunkSizeKey));
        }
        if ($this->totalSize < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', $totalSizeKey));
        } elseif ($this->totalSize <= $this->index * $this->chunkSize) {
            throw new InvalidArgumentException(
                sprintf('`%s` must be greater than the multiple of `%s` and `%s`', $totalSizeKey, $chunkSizeKey, $indexKey)
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
        return $this->index * $this->chunkSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnd(): float
    {
        $end = (($this->index + 1) * $this->chunkSize) - 1;

        $sizeIndex = $this->totalSize - 1;
        if ($end > ($sizeIndex)) {
            return $sizeIndex;
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

    /**
     * @param $uploadedChunks
     *
     * @return float
     */
    public function getPercentage($uploadedChunks): float
    {
        return floor(count($uploadedChunks) / $this->numberOfChunks * 100);
    }

    /**
     * @param $uploadedChunks
     *
     * @return bool
     */
    public function isFinished($uploadedChunks): bool
    {
        return $this->numberOfChunks === count($uploadedChunks);
    }
}
