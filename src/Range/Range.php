<?php

namespace CodingSocks\UploadHandler\Range;

interface Range
{

    /**
     * Get the start position of the current chunk in bytes
     *
     * @return float
     */
    public function getStart(): float;

    /**
     * Get the end position of the current chunk in bytes
     *
     * @return float
     */
    public function getEnd(): float;

    /**
     * Get the total file size in bytes
     *
     * @return float
     */
    public function getTotal(): float;

    /**
     * Check if the current chunk is the first one of the file
     *
     * @return bool
     */
    public function isFirst(): bool;

    /**
     * Check if the current chunk is the last one of the file
     *
     * @return bool
     */
    public function isLast(): bool;
}
