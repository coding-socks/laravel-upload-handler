<?php

namespace CodingSocks\UploadHandler\Tests\Response;

use CodingSocks\UploadHandler\Response\PercentageJsonResponse;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PercentageJsonResponseTest extends TestCase
{
    use MakesHttpRequests;

    public static function percentageProvider()
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
        if (class_exists('\Illuminate\Testing\TestResponse')) {
            $response = TestResponse::fromBaseResponse(new PercentageJsonResponse($percentage));
        } else {
            $response = TestResponse::fromBaseResponse(new PercentageJsonResponse($percentage));
        }

        $response->assertSuccessful();
        $response->assertStatus(Response::HTTP_OK);
        $response->assertExactJson($expectedContent);
    }
}
