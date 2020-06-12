<?php

namespace LaraCrafts\ChunkUploader\Tests\Identifier;

use Illuminate\Support\Facades\Session;
use LaraCrafts\ChunkUploader\Identifier\SessionIdentifier;
use Orchestra\Testbench\TestCase;

class SessionIdentifierTest extends TestCase
{
    /**
     * @var \LaraCrafts\ChunkUploader\Identifier\SessionIdentifier
     */
    private $identifier;

    protected function setUp(): void
    {
        parent::setUp();

        Session::shouldReceive('getId')
            ->andReturn('frgYt7cPmNGtORpRCo4xvFIrWklzFqc2mnO6EE6b');

        $this->identifier = new SessionIdentifier();
    }

    public function testGenerateIdentifier()
    {
        $identifier = $this->identifier->generateIdentifier('test.txt');
        $this->assertEquals('2494cefe4d234bd331aeb4514fe97d810efba29b', $identifier);
    }

    public function testGenerateIdentifierWithoutExtension()
    {
        $identifier = $this->identifier->generateIdentifier('test');
        $this->assertEquals('3b7b99bf70a98a544319cf3bad9e912e1b89984d', $identifier);
    }

    public function testUploadedFileIdentifierName()
    {
        $identifier = $this->identifier->generateFileIdentifier('test.txt', 200);
        $this->assertEquals('5d5115c1064c6e9dead0b7b71506bdfe273fd11c', $identifier);
    }

    public function testUploadedFileIdentifierNameWithoutExtension()
    {
        $identifier = $this->identifier->generateFileIdentifier('test', 200);
        $this->assertEquals('19daf2dc95ccbc0c856a1ce7a13c949f9e81fd2e', $identifier);
    }
}
