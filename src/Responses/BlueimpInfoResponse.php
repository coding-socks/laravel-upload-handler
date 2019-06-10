<?php

namespace LaraCrafts\ChunkUploader\Responses;

use Illuminate\Http\JsonResponse;

class BlueimpInfoResponse extends JsonResponse
{
    public function __construct()
    {
        parent::__construct([], 200, [
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Content-Disposition' => 'inline; filename="files.json"',
            'X-Content-Type-Options' => 'nosniff',
            'Vary' => 'Accept',
        ]);
    }
}
