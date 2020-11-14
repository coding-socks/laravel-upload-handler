<?php

namespace CodingSocks\UploadHandler\Range;

use Illuminate\Http\Request;
use InvalidArgumentException;

class PluploadRange implements Range
{
    private static $CHUNK_NUMBER_PARAMETER_NAME = 'chunk';
    private static $TOTAL_CHUNK_NUMBER_PARAMETER_NAME = 'chunks';

    /**
     * @var float
     */
    protected $current;

    /**
     * @var float
     */
    protected $total;

    /**
     * NgFileUploadRange constructor.
     *
     * @param \Illuminate\Http\Request|\Symfony\Component\HttpFoundation\ParameterBag $request
     */
    public function __construct($request)
    {
        if ($request instanceof Request) {
            $request = $request->request;
        }

        $this->current = (float) $request->get(self::$CHUNK_NUMBER_PARAMETER_NAME);
        $this->total = (float) $request->get(self::$TOTAL_CHUNK_NUMBER_PARAMETER_NAME);

        if ($this->current < 0) {
            throw new InvalidArgumentException(
                sprintf('`%s` must be greater than or equal to zero', self::$CHUNK_NUMBER_PARAMETER_NAME)
            );
        }
        if ($this->total < 1) {
            throw new InvalidArgumentException(
                sprintf('`%s` must be greater than zero', self::$TOTAL_CHUNK_NUMBER_PARAMETER_NAME)
            );
        }
        if ($this->current >= $this->total) {
            throw new InvalidArgumentException(
                sprintf('`%s` must be less than `%s`', self::$CHUNK_NUMBER_PARAMETER_NAME, self::$TOTAL_CHUNK_NUMBER_PARAMETER_NAME)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStart(): float
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnd(): float
    {
        return $this->current + 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * {@inheritDoc}
     */
    public function isFirst(): bool
    {
        return $this->current === 0.0;
    }

    /**
     * {@inheritDoc}
     */
    public function isLast(): bool
    {
        return $this->current >= ($this->total - 1);
    }

    /**
     * @return float
     */
    public function getPercentage(): float
    {
        return floor(($this->current + 1) / $this->total * 100);
    }
}
