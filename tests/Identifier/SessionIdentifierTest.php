<?php

namespace CodingSocks\UploadHandler\Tests\Identifier;

use CodingSocks\UploadHandler\Identifier\SessionIdentifier;
use Illuminate\Support\Facades\Session;
use Orchestra\Testbench\TestCase;

class SessionIdentifierTest extends TestCase
{
    /**
     * @var \CodingSocks\UploadHandler\Identifier\SessionIdentifier
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
        $identifier = $this->identifier->generateIdentifier('any_string');
        $this->assertEquals('b41d07049729f460973494395f9bf8fe23834d48', $identifier);
    }

    public function testUploadedFileIdentifierName()
    {
        $identifier = $this->identifier->generateFileIdentifier(200, 'any_filename.ext');
        $this->assertEquals('ec1669bf4dee72e6dd30b94d2d29413601f1b69b', $identifier);
    }
}
