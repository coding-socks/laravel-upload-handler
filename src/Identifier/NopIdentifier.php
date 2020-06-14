<?php

namespace CodingSocks\ChunkUploader\Identifier;

class NopIdentifier extends Identifier
{
    public function generateIdentifier(string $data): string
    {
        return $data;
    }
}
