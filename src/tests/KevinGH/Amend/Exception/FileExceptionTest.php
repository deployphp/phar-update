<?php

namespace KevinGH\Amend\Tests\Exception;

use KevinGH\Amend\Exception\FileException;
use PHPUnit_Framework_TestCase;

class FileExceptionTest extends PHPUnit_Framework_TestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = new FileException('problem', 'message');
    }

    public function testCreate()
    {
        $test = @$doesNotExist;

        $exception = FileException::create('problem');

        $this->assertEquals('problem', $exception->getProblemFile());
        $this->assertRegExp(
            '/Undefined variable: doesNotExist/',
            $exception->getMessage()
        );
    }

    public function testGetMessage()
    {
        $this->assertEquals('message', $this->exception->getMessage());
    }

    public function testGetProblemFile()
    {
        $this->assertEquals('problem', $this->exception->getProblemFile());
    }
}