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
    public abstract function generateIdentifier(string $data): string;

    /**
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return string
     */
    public function generateUploadedFileIdentifierName(UploadedFile $file): string
    {
        $data = $file->getClientOriginalName();

        $filename = $this->generateIdentifier($data);

        // On windows you can not create a file whose name ends with a dot
        if ($file->getClientOriginalExtension()) {
            $filename .= '.'.$file->getClientOriginalExtension();
        }

        return $filename;
    }
}
