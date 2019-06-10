<?php

namespace LaraCrafts\ChunkUploader\Contracts;

interface Range
{

    /**
     * @return float
     */
    public function getStart(): float;

    /**
     * @return float
     */
    public function getEnd(): float;

    /**
     * @return float
     */
    public function getTotal(): float;

    /**
     * @return bool
     */
    public function isFirst(): bool;

    /**
     * @return bool
     */
    public function isLast(): bool;
}
