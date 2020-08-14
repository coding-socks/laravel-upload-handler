<?php

namespace CodingSocks\UploadHandler\Range;

use Illuminate\Http\Request;
use InvalidArgumentException;

abstract class RequestBodyRange implements Range
{
    protected $index;

    protected $numberOfChunks;

    protected $totalSize;

    protected $chunkSize;

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

        $this->index = (int) $request->get($indexKey);
        $this->numberOfChunks = (int) $request->get($numberOfChunksKey);
        $this->chunkSize = (int) $request->get($chunkSizeKey);
        // Must be double (which is an alias for float) for 32 bit systems
        $this->totalSize = (double) $request->get($totalSizeKey);

        $this->validateNumberOfChunks($numberOfChunksKey);
        $this->validateIndexKey($indexKey, $numberOfChunksKey);
        $this->validateChunkSize($chunkSizeKey);
        $this->validateTotalSize($indexKey, $numberOfChunksKey, $chunkSizeKey, $totalSizeKey);
    }

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
    abstract protected function validateIndexKey(string $indexKey, string $numberOfChunksKey): void;

    /**
     * @param string $chunkSizeKey
     */
    protected function validateChunkSize(string $chunkSizeKey): void
    {
        if ($this->chunkSize < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', $chunkSizeKey));
        }
    }

    /**
     * @param string $indexKey
     * @param string $numberOfChunksKey
     * @param string $chunkSizeKey
     * @param string $totalSizeKey
     */
    abstract protected function validateTotalSize(string $indexKey, string $numberOfChunksKey, string $chunkSizeKey, string $totalSizeKey): void;

    /**
     * {@inheritDoc}
     */
    abstract public function getStart(): float;

    /**
     * {@inheritDoc}
     */
    abstract public function getEnd(): float;

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
    abstract public function isFirst(): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function isLast(): bool;

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
