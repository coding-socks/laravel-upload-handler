<?php

namespace LaraCrafts\ChunkUploader\Identifier;

abstract class Identifier
{
    /**
     * @param string $data
     *
     * @return string
     */
    abstract public function generateIdentifier(string $data): string;

    /**
     * @param string $originalFilename
     * @param float $totalSize
     *
     * @return string
     */
    public function generateFileIdentifier(string $originalFilename, float $totalSize): string
    {
        $data = $totalSize . '_' . $originalFilename;

        return $this->generateIdentifier($data);
    }
}
