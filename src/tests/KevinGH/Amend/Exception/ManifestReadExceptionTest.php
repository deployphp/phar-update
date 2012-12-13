<?php

namespace KevinGH\Amend\Tests\Exception;

use KevinGH\Amend\Exception\ManifestReadException;
use PHPUnit_Framework_TestCase;

class ManifestReadExceptionTest extends PHPUnit_Framework_TestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = new ManifestReadException('url', 'message');
    }

    public function testGetMessage()
    {
        $this->assertEquals('message', $this->exception->getMessage());
    }

    public function testGetUrl()
    {
        $this->assertEquals('url', $this->exception->getUrl());
    }
}