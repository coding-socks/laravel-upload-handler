<?php

namespace LaraCrafts\ChunkUploader\Ranges;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use LaraCrafts\ChunkUploader\Contracts\Range as RangeContract;

class ContentRange implements RangeContract
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
     * @param string|HeaderBag $contentRange
     *
     * @throws \HttpHeaderException
     */
    public function __construct($contentRange)
    {
        if ($contentRange instanceof ParameterBag) {
            $contentRange = $contentRange->get('content-range');
        }

        if (preg_match("/bytes (\d+)-(\d+)\/(\d+)/", $contentRange, $matches) === false) {
            throw new \HttpHeaderException("Content Range header is missing or invalid");
        }

        $this->start = $this->numericValue($matches[1]);
        $this->end = $this->numericValue($matches[2]);
        $this->total = $this->numericValue($matches[3]);
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
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'The content range value is too large');
        }

        return $floatVal;
    }

    /**
     * @return float
     */
    public function getStart(): float
    {
        return $this->start;
    }

    /**
     * @return float
     */
    public function getEnd(): float
    {
        return $this->end;
    }

    /**
     * @return float
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->start === 0;
    }

    /**
     * @return bool
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
