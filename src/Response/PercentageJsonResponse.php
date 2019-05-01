<?php

namespace LaraCrafts\ChunkUploader\Response;

use Illuminate\Http\JsonResponse;

class PercentageJsonResponse extends JsonResponse
{
    use Response;

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
