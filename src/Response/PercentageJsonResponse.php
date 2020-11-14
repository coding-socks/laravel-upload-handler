<?php

namespace CodingSocks\UploadHandler\Response;

use Illuminate\Http\JsonResponse;

class PercentageJsonResponse extends JsonResponse
{
    /**
     * @var int
     */
    protected $percentage;

    /**
     * BlueimpResponse constructor.
     *
     * @param int $percentage
     */
    public function __construct(int $percentage)
    {
        parent::__construct([
            'done' => $percentage,
        ]);

        $this->percentage = $percentage;
    }
}
