<?php

namespace CodingSocks\UploadHandler\Identifier;

use Illuminate\Support\Facades\Session;

class SessionIdentifier extends Identifier
{
    public function generateIdentifier(string $data): string
    {
        return sha1(Session::getId() . $data);
    }
}
