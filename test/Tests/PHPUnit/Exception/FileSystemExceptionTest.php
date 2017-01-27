<?php

namespace Deployer\Component\PHPUnit\Tests\Exception;

use Exception;
use Deployer\Component\PHPUnit\Exception\FileSystemException;
use PHPUnit_Framework_TestCase as TestCase;

class FileSystemExceptionTest extends TestCase
{
    /**
     * @var FileSystemException
     */
    private $exception;

    private $last;

    public function testGetRelevantPath()
    {
        $this->assertEquals('/test/path', $this->exception->getRelevantPath());
    }

    public function testInvalidPath()
    {
        $exception = FileSystemException::invalidPath('/test/path');

        $this->assertEquals('/test/path', $exception->getRelevantPath());
        $this->assertEquals(
            'The path "/test/path" does not exist.',
            $exception->getMessage()
        );
    }

    public function testLastError()
    {
        @$test;

        $exception = FileSystemException::lastError('/test/path', 'test: ');

        $this->assertEquals('/test/path', $exception->getRelevantPath());
        $this->assertEquals(
            'test: Undefined variable: test',
            $exception->getMessage()
        );
    }

    protected function setUp()
    {
        $this->last = new Exception();
        $this->exception = new FileSystemException(
            '/test/path',
            'Test message.',
            $this->last
        );
    }
}
