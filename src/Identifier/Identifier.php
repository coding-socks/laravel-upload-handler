<?php

namespace CodingSocks\ChunkUploader\Identifier;

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
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return string
     */
    public function generateUploadedFileIdentifierName(UploadedFile $file): string
    {
        $data = $file->getClientOriginalName();

        return $this->generateIdentifier($data) . '.' . $file->guessExtension();
    }
}
