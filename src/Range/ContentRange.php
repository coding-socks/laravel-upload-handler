<?php

namespace CodingSocks\UploadHandler\Range;

use CodingSocks\UploadHandler\Exception\RequestEntityTooLargeHttpException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\HeaderBag;

class ContentRange implements Range
{
    /**
     * @var float
     */
    protected $start;

    /**
     * @var float
     */
    protected $end;

    /**
     * @var float
     */
    protected $total;

    /**
     * ContentRange constructor.
     *
     * @param string|\Symfony\Component\HttpFoundation\HeaderBag|\Illuminate\Http\Request $contentRange
     */
    public function __construct($contentRange)
    {
        if ($contentRange instanceof Request) {
            $contentRange = $contentRange->header('content-range');
        } elseif ($contentRange instanceof HeaderBag) {
            $contentRange = $contentRange->get('content-range');
        }

        if (preg_match('#bytes (\d+)-(\d+)/(\d+)#', $contentRange, $matches) !== 1) {
            throw new InvalidArgumentException('Content Range header is missing or invalid');
        }

        $this->start = $this->numericValue($matches[1]);
        $this->end = $this->numericValue($matches[2]);
        $this->total = $this->numericValue($matches[3]);

        if ($this->end < $this->start) {
            throw new InvalidArgumentException('Range end must be greater than or equal to range start');
        }
        if ($this->total <= $this->end) {
            throw new InvalidArgumentException('Size must be greater than range end');
        }
    }

    /**
     * Converts the string value to float - throws exception if float value is exceeded.
     *
     * @param string $value
     *
     * @return float
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function numericValue($value): float
    {
        $floatVal = floatval($value);

        if ($floatVal === INF) {
            throw new RequestEntityTooLargeHttpException('The content range value is too large');
        }

        return $floatVal;
    }

    /**
     * {@inheritDoc}
     */
    public function getStart(): float
    {
        return $this->start;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnd(): float
    {
        return $this->end;
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
        return $this->start === 0.0;
    }

    /**
     * {@inheritDoc}
     */
    public function isLast(): bool
    {
        return $this->end >= ($this->total - 1);
    }

    /**
     * @return float
     */
    public function getPercentage(): float
    {
        return floor(($this->end + 1) / $this->total * 100);
    }
}
