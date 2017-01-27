<?php

namespace Deployer\Component\Version\Tests\Exception;

use Deployer\Component\PHPUnit\TestCase;
use Deployer\Component\Version\Exception\InvalidIdentifierException;

class InvalidIdentifierExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $exception = new InvalidIdentifierException('test');

        $this->assertEquals(
            'The identifier "test" is invalid.',
            $exception->getMessage()
        );
    }

    /**
     * @depends testConstruct
     */
    public function testGetIdentifier()
    {
        $exception = new InvalidIdentifierException('test');

        $this->assertEquals('test', $exception->getIdentifier());
    }
}
