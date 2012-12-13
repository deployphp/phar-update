<?php

namespace KevinGH\Amend\Tests\Exception;

use KevinGH\Amend\Exception\ManifestJsonException;
use PHPUnit_Framework_TestCase;

class ManifestJsonExceptionTest extends PHPUnit_Framework_TestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = new ManifestJsonException('url', 123);
    }

    public function testConstructor()
    {
        $this->assertEquals(
            'The updates manifest file is invalid.',
            $this->exception->getMessage()
        );
    }

    public function testGetCode()
    {
        $this->assertSame(123, $this->exception->getCode());
    }

    public function testGetUrl()
    {
        $this->assertEquals('url', $this->exception->getUrl());
    }
}