<?php

namespace CodingSocks\UploadHandler\Tests\Range;

use CodingSocks\UploadHandler\Range\NgFileUploadRange;
use Illuminate\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class NgFileUploadRangeTest extends TestCase
{
    public static function invalidArgumentProvider()
    {
        return [
            'Chunk number less than zero' => [-1, 10, 10, 100, '`_chunkNumber` must be greater than or equal to zero'],
            'Chunk size less than one' => [0, 0, 10, 100, '`_chunkSize` must be greater than zero'],
            'Current chunk size less than one' => [0, 10, 0, 100, '`_currentChunkSize` must be greater than zero'],
            'Total size less than one' => [0, 10, 10, 0, '`_totalSize` must be greater than zero'],
        ];
    }

    /**
     * @dataProvider invalidArgumentProvider
     *
     * @param $chunkNumber
     * @param $chunkSize
     * @param $currentChunkSize
     * @param $totalSize
     * @param $expectedExceptionMessage
     */
    public function testArgumentValidation($chunkNumber, $chunkSize, $currentChunkSize, $totalSize, $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createRequestBodyRange($chunkNumber, $chunkSize, $currentChunkSize, $totalSize);
    }

    public function testIsFirst()
    {
        $range = $this->createRequestBodyRange(0, 10, 10, 30);
        $this->assertTrue($range->isFirst());

        $range = $this->createRequestBodyRange(1, 10, 10, 30);
        $this->assertFalse($range->isFirst());
    }

    public function testIsLast()
    {
        $range = $this->createRequestBodyRange(2, 10, 10, 30);
        $this->assertTrue($range->isLast());

        $range = $this->createRequestBodyRange(1, 10, 10, 30);
        $this->assertFalse($range->isLast());
    }

    public function testIsFirstAndIsLast()
    {
        $range = $this->createRequestBodyRange(0, 10, 10, 10);
        $this->assertTrue($range->isLast());
        $this->assertTrue($range->isLast());
    }

    public function testGetTotal()
    {
        $range = $this->createRequestBodyRange(4, 10, 10, 190);
        $this->assertEquals(190, $range->getTotal());
    }

    public function testGetStart()
    {
        $range = $this->createRequestBodyRange(4, 10, 10, 190);
        $this->assertEquals(40, $range->getStart());
    }

    public function testGetEnd()
    {
        $range = $this->createRequestBodyRange(4, 10, 10, 190);
        $this->assertEquals(49, $range->getEnd());
    }

    public function testGetPercentage()
    {
        $range = $this->createRequestBodyRange(4, 10, 10, 100);
        $this->assertEquals(50, $range->getPercentage());

        $range = $this->createRequestBodyRange(9, 10, 10, 100);
        $this->assertEquals(100, $range->getPercentage());
    }

    public function testCreateFromRequest()
    {
        $request = new Request([], [
            '_chunkNumber' => (string) 5,
            '_chunkSize' => (string) 10,
            '_currentChunkSize' => (string) 10,
            '_totalSize' => (string) 100,
        ]);

        $range = new NgFileUploadRange($request);

        $this->assertEquals(50, $range->getStart());
        $this->assertEquals(59, $range->getEnd());
        $this->assertEquals(100, $range->getTotal());
    }

    /**
     * @param int $chunkNumber
     * @param int $chunkSize
     * @param int $currentChunkSize
     * @param float $totalSize
     *
     * @return \CodingSocks\UploadHandler\Range\NgFileUploadRange
     */
    private function createRequestBodyRange(int $chunkNumber, int $chunkSize, int $currentChunkSize, float $totalSize)
    {
        $request = new ParameterBag([
            '_chunkNumber' => (string) $chunkNumber,
            '_chunkSize' => (string) $chunkSize,
            '_currentChunkSize' => (string) $currentChunkSize,
            '_totalSize' => (string) $totalSize,
        ]);

        return new NgFileUploadRange($request);
    }
}
