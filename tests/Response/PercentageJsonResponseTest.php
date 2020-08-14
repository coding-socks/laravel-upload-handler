<?php

namespace CodingSocks\UploadHandler\Response;

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class PercentageJsonResponseTest extends TestCase
{
    use MakesHttpRequests;

    public function percentageProvider()
    {
        return [
            [21, ['done' => 21]],
            [50, ['done' => 50]],
            [73, ['done' => 73]],
            [100, ['done' => 100]],
        ];
    }

    /**
     * @dataProvider percentageProvider
     *
     * @param int $percentage
     * @param array $expectedContent
     */
    public function testContent(int $percentage, array $expectedContent)
    {
        $response = $this->createTestResponse(new PercentageJsonResponse($percentage));

        $response->assertSuccessful();
        $response->assertStatus(Response::HTTP_OK);
        $response->assertExactJson($expectedContent);
    }
}
