<?php

namespace LaraCrafts\ChunkUploader\Responses;

use Illuminate\Http\JsonResponse;
use LaraCrafts\ChunkUploader\Concerns\ManagesFileResponses;

class BlueimpUploadResponse extends JsonResponse
{
    use ManagesFileResponses;

    /**
     * @var int
     */
    private $percentage;

    /**
     * BlueimpResponse constructor.
     *
     * @param int $percentage
     * @param string[] $chunks
     * @param string|null $mergedFile
     */
    public function __construct(int $percentage, array $chunks, string $mergedFile = null)
    {
        parent::__construct([
            'done' => $percentage,
        ]);

        $this->chunks = $chunks;
        $this->mergedFile = $mergedFile;
        $this->percentage = $percentage;
    }
}
