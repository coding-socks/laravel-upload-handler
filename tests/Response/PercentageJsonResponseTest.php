<?php

namespace LaraCrafts\ChunkUploader\Response;

use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class PercentageJsonResponseTest extends TestCase
{
    public function percentageProvider()
    {
        return [
            [21, '{"done":21}'],
            [50, '{"done":50}'],
            [73, '{"done":73}'],
            [100, '{"done":100}'],
        ];
    }

    /**
     * @dataProvider percentageProvider
     *
     * @param int $percentage
     * @param string $expectedContent
     */
    public function testContent(int $percentage, string $expectedContent)
    {
        $response = new PercentageJsonResponse($percentage);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($expectedContent, $response->getContent());
    }
}
