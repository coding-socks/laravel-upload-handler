<?php

namespace CodingSocks\UploadHandler\Range;

use InvalidArgumentException;

class NgFileUploadRange implements Range
{
    private static $CHUNK_NUMBER_PARAMETER_NAME = '_chunkNumber';
    private static $CHUNK_SIZE_PARAMETER_NAME = '_chunkSize';
    private static $CURRENT_CHUNK_SIZE_PARAMETER_NAME = '_currentChunkSize';
    private static $TOTAL_SIZE_PARAMETER_NAME = '_totalSize';

    private $chunkNumber;

    private $chunkSize;

    private $currentChunkSize;

    private $totalSize;

    /**
     * NgFileUploadRange constructor.
     *
     * @param \Illuminate\Http\Request|\Symfony\Component\HttpFoundation\ParameterBag $request
     */
    public function __construct($request)
    {
        $this->chunkNumber = (int) $request->get(self::$CHUNK_NUMBER_PARAMETER_NAME);
        $this->chunkSize = (int) $request->get(self::$CHUNK_SIZE_PARAMETER_NAME);
        $this->currentChunkSize = (int) $request->get(self::$CURRENT_CHUNK_SIZE_PARAMETER_NAME);
        $this->totalSize = (double) $request->get(self::$TOTAL_SIZE_PARAMETER_NAME);

        if ($this->chunkNumber < 0) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than or equal to zero', self::$CHUNK_NUMBER_PARAMETER_NAME));
        }
        if ($this->chunkSize < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', self::$CHUNK_SIZE_PARAMETER_NAME));
        }
        if ($this->currentChunkSize < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', self::$CURRENT_CHUNK_SIZE_PARAMETER_NAME));
        }
        if ($this->totalSize < 1) {
            throw new InvalidArgumentException(sprintf('`%s` must be greater than zero', self::$TOTAL_SIZE_PARAMETER_NAME));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStart(): float
    {
        return $this->chunkNumber * $this->chunkSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnd(): float
    {
        return $this->getStart() + $this->currentChunkSize - 1;
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
        return $this->chunkNumber === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isLast(): bool
    {
        return $this->getEnd() === ($this->getTotal() - 1);
    }

    /**
     * @return float
     */
    public function getPercentage(): float
    {
        return floor(($this->getEnd() + 1) / $this->getTotal() * 100);
    }
}
