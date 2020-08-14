<?php

namespace CodingSocks\UploadHandler\Identifier;

abstract class Identifier
{
    /**
     * @param string $data
     *
     * @return string
     */
    abstract public function generateIdentifier(string $data): string;

    /**
     * @param float $totalSize
     * @param string $originalFilename
     *
     * @return string
     */
    public function generateFileIdentifier(float $totalSize, string $originalFilename): string
    {
        $data = $totalSize . '_' . $originalFilename;

        return $this->generateIdentifier($data);
    }
}
