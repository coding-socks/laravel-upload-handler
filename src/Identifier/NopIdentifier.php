<?php

namespace CodingSocks\UploadHandler\Identifier;

class NopIdentifier extends Identifier
{
    public function generateIdentifier(string $data): string
    {
        return $data;
    }
}
