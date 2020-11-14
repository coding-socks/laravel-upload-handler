<?php

namespace CodingSocks\UploadHandler\Identifier;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;

class AuthIdentifier extends Identifier
{
    public function generateIdentifier(string $data): string
    {
        if (!Auth::check()) {
            throw new UnauthorizedException();
        }
        return sha1(Auth::id() . '_' . $data);
    }
}
