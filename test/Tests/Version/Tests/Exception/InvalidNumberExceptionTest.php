<?php

namespace Deployer\Component\Version\Tests\Exception;

use Deployer\Component\PHPUnit\TestCase;
use Deployer\Component\Version\Exception\InvalidNumberException;

class InvalidNumberExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $exception = new InvalidNumberException('test');

        $this->assertEquals(
            'The version number "test" is invalid.',
            $exception->getMessage()
        );
    }

    /**
     * @depends testConstruct
     */
    public function testGetNumber()
    {
        $exception = new InvalidNumberException('test');

        $this->assertEquals('test', $exception->getNumber());
    }
}
