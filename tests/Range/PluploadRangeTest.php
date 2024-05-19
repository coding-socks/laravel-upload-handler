<?php

namespace CodingSocks\UploadHandler\Tests\Range;

use CodingSocks\UploadHandler\Range\PluploadRange;
use Illuminate\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class PluploadRangeTest extends TestCase
{
    public static function invalidArgumentProvider()
    {
        return [
            'Number of chunks size equal to zero' => [-1, 10, '`chunk` must be greater than or equal to zero'],
            'Number of chunks size smaller than zero' => [0, 0, '`chunks` must be greater than zero'],
            'Index smaller than zero' => [10, 10, '`chunk` must be less than `chunks`'],
        ];
    }

    /**
     * @dataProvider invalidArgumentProvider
     *
     * @param $chunk
     * @param $chunks
     * @param $expectedExceptionMessage
     */
    public function testArgumentValidation($chunk, $chunks, $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createRequestBodyRange($chunk, $chunks);
    }

    public function testIsFirst()
    {
        $range = $this->createRequestBodyRange(0, 20);
        $this->assertTrue($range->isFirst());
    }

    public function testIsLast()
    {
        $range = $this->createRequestBodyRange(19, 20);
        $this->assertTrue($range->isLast());
    }

    public function testIsFirstAndIsLast()
    {
        $range = $this->createRequestBodyRange(0, 1);
        $this->assertTrue($range->isFirst());
        $this->assertTrue($range->isLast());
    }

    public function testGetTotal()
    {
        $range = $this->createRequestBodyRange(4, 20);
        $this->assertEquals(20, $range->getTotal());
    }

    public function testGetStart()
    {
        $range = $this->createRequestBodyRange(4, 20);
        $this->assertEquals(4, $range->getStart());
    }

    public function testGetEnd()
    {
        $range = $this->createRequestBodyRange(4, 20);
        $this->assertEquals(5, $range->getEnd());
    }

    public function testGetPercentage()
    {
        $range = $this->createRequestBodyRange(4, 20);
        $this->assertEquals(25, $range->getPercentage());
    }

    public function testCreateFromRequest()
    {
        $request = new Request([], [
            'chunk' => 4,
            'chunks' => 10,
        ]);

        $range = new PluploadRange($request);

        $this->assertEquals(4, $range->getStart());
        $this->assertEquals(5, $range->getEnd());
        $this->assertEquals(10, $range->getTotal());
    }

    /**
     * @param float $chunk
     * @param float $chunks
     *
     * @return \CodingSocks\UploadHandler\Range\PluploadRange
     */
    private function createRequestBodyRange(float $chunk, float $chunks)
    {
        $request = new ParameterBag([
            'chunk' => (string) $chunk,
            'chunks' => (string) $chunks,
        ]);

        return new PluploadRange($request);
    }
}
