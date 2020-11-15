<?php

namespace CodingSocks\UploadHandler\Tests\Range;

use CodingSocks\UploadHandler\Exception\RequestEntityTooLargeHttpException;
use CodingSocks\UploadHandler\Range\ContentRange;
use Illuminate\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;

class ContentRangeTest extends TestCase
{
    public function invalidArgumentProvider()
    {
        return [
            'Null' => [null, 'Content Range header is missing or invalid'],
            'Empty string' => ['', 'Content Range header is missing or invalid'],
            'Invalid string' => ['invalid string', 'Content Range header is missing or invalid'],
            'End greater than start' => ['bytes 40-39/200', 'Range end must be greater than or equal to range start'],
            'Total equal to end' => ['bytes 40-49/49', 'Size must be greater than range end'],
            'Total greater than end' => ['bytes 40-49/48', 'Size must be greater than range end'],
        ];
    }

    /**
     * @dataProvider invalidArgumentProvider
     *
     * @param $contentRange
     * @param $expectedExceptionMessage
     */
    public function testArgumentValidation($contentRange, $expectedExceptionMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        new ContentRange($contentRange);
    }

    public function testRequestEntityTooLargeHttpException()
    {
        $this->expectException(RequestEntityTooLargeHttpException::class);
        $this->expectExceptionMessage('The content range value is too large');

        new ContentRange(sprintf('bytes 40-49/%s', str_repeat('9', 350)));
    }

    public function testIsFirst()
    {
        $range = new ContentRange('bytes 0-9/200');
        $this->assertTrue($range->isFirst());
    }

    public function testIsLast()
    {
        $range = new ContentRange('bytes 190-199/200');
        $this->assertTrue($range->isLast());
    }

    public function testIsFirstAndIsLast()
    {
        $range = new ContentRange('bytes 0-9/10');
        $this->assertTrue($range->isFirst());
        $this->assertTrue($range->isLast());
    }

    public function testGetTotal()
    {
        $range = new ContentRange('bytes 40-49/200');
        $this->assertEquals(200, $range->getTotal());
    }

    public function testGetStart()
    {
        $range = new ContentRange('bytes 40-49/200');
        $this->assertEquals(40, $range->getStart());
    }

    public function testGetEnd()
    {
        $range = new ContentRange('bytes 40-49/200');
        $this->assertEquals(49, $range->getEnd());
    }

    public function testGetPercentage()
    {
        $range = new ContentRange('bytes 40-49/200');
        $this->assertEquals(25, $range->getPercentage());
    }

    public function testCreateFromHeaderBag()
    {
        $range = new ContentRange(new HeaderBag([
            'Content-Range' => 'bytes 40-49/200',
        ]));

        $this->assertEquals(200, $range->getTotal());
        $this->assertEquals(40, $range->getStart());
        $this->assertEquals(49, $range->getEnd());
        $this->assertEquals(25, $range->getPercentage());
    }

    public function testCreateFromRequest()
    {
        $request = new Request();
        $request->headers->set('content-range', 'bytes 40-49/200');

        $range = new ContentRange($request);

        $this->assertEquals(200, $range->getTotal());
        $this->assertEquals(40, $range->getStart());
        $this->assertEquals(49, $range->getEnd());
        $this->assertEquals(25, $range->getPercentage());
    }
}
