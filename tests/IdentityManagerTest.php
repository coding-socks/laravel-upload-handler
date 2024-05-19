<?php

namespace CodingSocks\UploadHandler\Tests;

use CodingSocks\UploadHandler\Identifier\AuthIdentifier;
use CodingSocks\UploadHandler\Identifier\NopIdentifier;
use CodingSocks\UploadHandler\Identifier\SessionIdentifier;
use CodingSocks\UploadHandler\IdentityManager;

class IdentityManagerTest extends TestCase
{
    /**
     * @var IdentityManager
     */
    private $manager;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new IdentityManager($this->app);
    }

    public static function availableDrivers()
    {
        return [
            'auth' => ['auth', AuthIdentifier::class],
            'nop' => ['nop', NopIdentifier::class],
            'session' => ['session', SessionIdentifier::class],
        ];
    }

    /**
     * @dataProvider availableDrivers
     */
    public function testDriverCreation($driverName, $expectedInstanceOf)
    {
        $driver = $this->manager->driver($driverName);
        $this->assertInstanceOf($expectedInstanceOf, $driver);
    }

    public function testDefaultDriver()
    {
        $driver = $this->manager->driver();
        $this->assertInstanceOf(SessionIdentifier::class, $driver);
    }
}
