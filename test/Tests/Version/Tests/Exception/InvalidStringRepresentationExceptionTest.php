<?php

namespace Deployer\Component\Version\Tests\Exception;

use Deployer\Component\PHPUnit\TestCase;
use Deployer\Component\Version\Exception\InvalidStringRepresentationException;

class InvalidStringRepresentationExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $exception = new InvalidStringRepresentationException('test');

        $this->assertEquals(
            'The version string representation "test" is invalid.',
            $exception->getMessage()
        );
    }

    /**
     * @depends testConstruct
     */
    public function testGetStringRepresentation()
    {
        $exception = new InvalidStringRepresentationException('test');

        $this->assertEquals('test', $exception->getVersion());
    }
}
