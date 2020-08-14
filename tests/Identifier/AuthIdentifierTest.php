<?php

namespace CodingSocks\UploadHandler\Tests\Identifier;

use CodingSocks\UploadHandler\Identifier\AuthIdentifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Orchestra\Testbench\TestCase;

class AuthIdentifierTest extends TestCase
{
    /**
     * @var \CodingSocks\UploadHandler\Identifier\Identifier
     */
    private $identifier;

    protected function setUp(): void
    {
        parent::setUp();

        Auth::shouldReceive('id')
            ->andReturn(100);

        $this->identifier = new AuthIdentifier();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Auth::clearResolvedInstances();
    }

    public function testGenerateIdentifierThrowsUnauthorizedException()
    {
        Auth::shouldReceive('check')
            ->andReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->identifier->generateIdentifier('any_string');
    }

    public function testGenerateIdentifier()
    {
        Auth::shouldReceive('check')
            ->andReturn(true);

        $identifier = $this->identifier->generateIdentifier('any_string');
        $this->assertEquals('2b2ea43a7652e1f7925c588b9ae7a31f09be3bf9', $identifier);
    }

    public function testUploadedFileIdentifierName()
    {
        Auth::shouldReceive('check')
            ->andReturn(true);

        $identifier = $this->identifier->generateFileIdentifier(200, 'any_filename.ext');
        $this->assertEquals('4317e3d56e27deda5bd84dd35830bff799736257', $identifier);
    }
}
