<?php

namespace LaraCrafts\ChunkUploader\Identifier;

use Illuminate\Http\UploadedFile;

abstract class Identifier
{
    /**
     * @param string $data
     *
     * @return string
     */
    abstract public function generateIdentifier(string $data): string;

    /**
     * @param \Illuminate\Http\UploadedFile $chunk
     * @param $totalSize
     *
     * @return string
     */
    public function generateFileIdentifierName(UploadedFile $chunk, $totalSize): string
    {
        $data = $totalSize . '_' . $chunk->getClientOriginalName();

        return $this->generateIdentifier($data);
    }
}
