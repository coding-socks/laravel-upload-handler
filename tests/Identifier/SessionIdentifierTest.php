<?php

namespace CodingSocks\ChunkUploader\Tests\Identifier;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use CodingSocks\ChunkUploader\Identifier\SessionIdentifier;
use Orchestra\Testbench\TestCase;

class SessionIdentifierTest extends TestCase
{
    /**
     * @var \CodingSocks\ChunkUploader\Identifier\SessionIdentifier
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
        $file = UploadedFile::fake()->create('test.txt', 100);
        $identifier = $this->identifier->generateUploadedFileIdentifierName($file);
        $this->assertEquals('2494cefe4d234bd331aeb4514fe97d810efba29b.txt', $identifier);
    }

    public function testUploadedFileIdentifierNameWithoutExtension()
    {
        $file = UploadedFile::fake()->create('test', 100);
        $identifier = $this->identifier->generateUploadedFileIdentifierName($file);
        $this->assertEquals('3b7b99bf70a98a544319cf3bad9e912e1b89984d.bin', $identifier);
    }
}
