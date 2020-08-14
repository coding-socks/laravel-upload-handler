<?php

namespace CodingSocks\UploadHandler\Tests\Range;

use CodingSocks\UploadHandler\Range\DropzoneRange;
use Illuminate\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class DropzoneRangeTest extends TestCase
{
    public function invalidArgumentProvider()
    {
        return [
            'Number of chunks size equal to zero' => [4, 0, 20, 190, '`numberOfChunks` must be greater than zero'],
            'Number of chunks size smaller than zero' => [4, -1, 20, 190, '`numberOfChunks` must be greater than zero'],
            'Index smaller than zero' => [-1, 10, 20, 190, '`index` must be greater than or equal to zero'],
            'Index equal to the number of chunks' => [10, 10, 20, 190, '`index` must be smaller than `numberOfChunks`'],
            'Index greater than the number of chunks' => [14, 10, 20, 190, '`index` must be smaller than `numberOfChunks`'],
            'Chunk size equal to zero' => [4, 10, 0, 190, '`chunkSize` must be greater than zero'],
            'Chunk size smaller than zero' => [4, 10, -1, 190, '`chunkSize` must be greater than zero'],
            'Total size equal to zero' => [4, 10, 20, 0, '`totalSize` must be greater than zero'],
            'Total size smaller than zero' => [4, 10, 20, -1, '`totalSize` must be greater than zero'],
            'Total size too small' => [4, 10, 20, 80, '`totalSize` must be greater than the multiple of `chunkSize` and `index`'],
            'Total size too big' => [4, 10, 20, 201, '`totalSize` must be smaller than or equal to the multiple of `chunkSize` and `numberOfChunks`'],
        ];
    }

    /**
     * @dataProvider invalidArgumentProvider
     *
     * @param $index
     * @param $numberOfChunks
     * @param $chunkSize
     * @param $totalSize
     * @param $expectedExceptionMessage
     */
    public function testArgumentValidation($index, $numberOfChunks, $chunkSize, $totalSize, $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createRequestBodyRange($index, $numberOfChunks, $chunkSize, $totalSize);
    }

    public function testIsFirst()
    {
        $range = $this->createRequestBodyRange(0, 2, 1, 2);
        $this->assertTrue($range->isFirst());

        $range = $this->createRequestBodyRange(1, 2, 1, 2);
        $this->assertFalse($range->isFirst());
    }

    public function testIsLast()
    {
        $range = $this->createRequestBodyRange(1, 2, 1, 2);
        $this->assertTrue($range->isLast());

        $range = $this->createRequestBodyRange(0, 2, 1, 2);
        $this->assertFalse($range->isLast());
    }

    public function testIsFirstAndIsLast()
    {
        $range = $this->createRequestBodyRange(0, 1, 1, 1);
        $this->assertTrue($range->isLast());
        $this->assertTrue($range->isLast());
    }

    public function testGetTotal()
    {
        $range = $this->createRequestBodyRange(4, 10, 20, 190);
        $this->assertEquals(190, $range->getTotal());
    }

    public function testGetStart()
    {
        $range = $this->createRequestBodyRange(4, 10, 20, 190);
        $this->assertEquals(80, $range->getStart());
    }

    public function testGetEnd()
    {
        $range = $this->createRequestBodyRange(4, 10, 20, 190);
        $this->assertEquals(99, $range->getEnd());

        $range = $this->createRequestBodyRange(9, 10, 20, 190);
        $this->assertEquals(189, $range->getEnd());
    }

    public function testGetPercentage()
    {
        $range = $this->createRequestBodyRange(4, 10, 20, 190);
        $this->assertEquals(100, $range->getPercentage(range(0, 9)));

        $range = $this->createRequestBodyRange(4, 10, 20, 190);
        $this->assertEquals(90, $range->getPercentage(range(0, 8)));
    }

    public function testIsFinished()
    {
        $range = $this->createRequestBodyRange(4, 10, 20, 190);
        $this->assertTrue($range->isFinished(range(0, 9)));

        $range = $this->createRequestBodyRange(4, 10, 20, 190);
        $this->assertFalse($range->isFinished(range(0, 8)));
    }

    public function testCreateFromRequest()
    {
        $request = new Request([], [
            'index' => 4,
            'numberOfChunks' => 10,
            'chunkSize' => 20,
            'totalSize' => 190,
        ]);

        $range = new DropzoneRange($request, 'index', 'numberOfChunks', 'chunkSize', 'totalSize');

        $this->assertEquals(80, $range->getStart());
        $this->assertEquals(99, $range->getEnd());
        $this->assertEquals(190, $range->getTotal());
    }

    /**
     * @param int $index
     * @param int $numberOfChunks
     * @param int $chunkSize
     * @param float $totalSize
     *
     * @return \CodingSocks\UploadHandler\Range\DropzoneRange
     */
    private function createRequestBodyRange(int $index, int $numberOfChunks, int $chunkSize, float $totalSize)
    {
        $request = new ParameterBag([
            'index' => (string) $index,
            'numberOfChunks' => (string) $numberOfChunks,
            'chunkSize' => (string) $chunkSize,
            'totalSize' => (string) $totalSize,
        ]);

        return new DropzoneRange($request, 'index', 'numberOfChunks', 'chunkSize', 'totalSize');
    }
}
