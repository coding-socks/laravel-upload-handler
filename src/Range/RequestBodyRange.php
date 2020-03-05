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
     * @param int $indexBase The base (offset) of the index
     */
    public function __construct($request, string $indexKey, string $numberOfChunksKey, string $chunkSizeKey, string $totalSizeKey, int $indexBase = 0)
    {
        if ($request instanceof Request) {
            $request = $request->request;
        }

        $this->index = (int) ($request->get($indexKey) - $indexBase);
        $this->numberOfChunks = (int) $request->get($numberOfChunksKey);
        $this->chunkSize = (int) $request->get($chunkSizeKey);
        // Must be double (which is an alias for float) for 32 bit systems
        $this->totalSize = (double) $request->get($totalSizeKey);

        if ($this->numberOfChunks <= 0) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', $numberOfChunksKey));
        }
        if ($this->index < 0) {
            if ($indexBase === 0) {
                throw new InvalidArgumentException(sprintf('`%s` must be greater than or equal to zero', $indexKey));
            }
            throw new InvalidArgumentException(sprintf('`%s` must be greater than or equal to %d', $indexKey, $indexBase));
        }
        if ($this->index >= $this->numberOfChunks) {
            if ($indexBase === 0) {
                throw new InvalidArgumentException(sprintf('`%s` must be smaller than `%s`', $indexKey, $numberOfChunksKey));
            }
            throw new InvalidArgumentException(sprintf('`%s` must be smaller than (`%s` + %d)', $indexKey, $numberOfChunksKey, $indexBase));
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
