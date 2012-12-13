<?php

namespace KevinGH\Amend\Tests\Exception;

use KevinGH\Amend\Exception\FileChecksumException;
use PHPUnit_Framework_TestCase;

class FileChecksumExceptionTest extends PHPUnit_Framework_TestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = new FileChecksumException(
            'a',
            'b',
            'c',
            'd'
        );
    }

    public function testGetNewChecksum()
    {
        $this->assertEquals('d', $this->exception->getNewChecksum());
    }

    public function testGetNewPath()
    {
        $this->assertEquals('c', $this->exception->getNewPath());
    }

    public function testGetSourceChecksum()
    {
        $this->assertEquals('b', $this->exception->getSourceChecksum());
    }

    public function testGetSourcePath()
    {
        $this->assertEquals('a', $this->exception->getSourcePath());
    }
}