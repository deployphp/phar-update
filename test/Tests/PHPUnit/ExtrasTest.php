<?php

namespace Deployer\Component\PHPUnit\Tests;

use Deployer\Component\PHPUnit\Extras;
use PHPUnit_Framework_TestCase;

class ExtrasTest extends PHPUnit_Framework_TestCase
{
    use Extras;

    public function calledMethod(&$var)
    {
        $var = 123;
    }

    public function testCallMethod()
    {
        $test = null;

        $this->callMethod($this, 'calledMethod', array(&$test));

        $this->assertSame(123, $test);
    }

    public function testCopyPathNotExist()
    {
        $this->setExpectedException(
            'Deployer\\Component\\PHPUnit\\Exception\\FileSystemException',
            'The path "doesNotExist" does not exist.'
        );

        $this->copyPath('doesNotExist', 'doesNotExist');
    }

    public function testCopyPathMkdirError()
    {
        $this->setExpectedException(
            'Deployer\\Component\\PHPUnit\\Exception\\FileSystemException',
            'The directory "/does/not/exist" could not be created'
        );

        $this->copyPath(__DIR__, '/does/not/exist');
    }

    // public function testCopyPathOpendirError()

    // public function testCopyPathCopyError()

    public function testCopy()
    {
        unlink($temp = tempnam(sys_get_temp_dir(), 'tst'));

        mkdir($temp);

        $this->copyPath(__DIR__ . '/example', $temp);

        $this->assertEquals('one', file_get_contents("$temp/one"));
        $this->assertEquals('two', file_get_contents("$temp/sub/two"));

        self::tearDown();

        $this->assertFileNotExists("$temp/sub/two");
        $this->assertFileNotExists("$temp/one");
        $this->assertFileNotExists($temp);
    }

    // public function testCreateDirMkdirError()

    // public function testCreateDirNamedMkdirError()

    public function testCreateDir()
    {
        $dir = $this->createDir('test');

        $this->assertTrue(is_dir($dir));
        $this->assertContains(sys_get_temp_dir(), $dir);
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR . 'test', $dir);

        self::tearDown();

        $this->assertFileNotExists($dir);
    }

    // public function testCreateFileTempnamError()

    // public function testCreateFileTouchError()

    public function testCreateFile()
    {
        $file = $this->createFile();

        $this->assertTrue(is_file($file));
        $this->assertContains(sys_get_temp_dir(), $file);

        self::tearDown();

        $this->assertFileNotExists($file);
    }

    public function testCreateFileNamed()
    {
        $file = $this->createFile('test');

        $this->assertContains(sys_get_temp_dir(), $file);
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR . 'test', $file);

        self::tearDown();

        $this->assertFileNotExists($file);
    }

    public function testFindMethodNotExist()
    {
        $this->setExpectedException(
            'Deployer\\Component\\PHPUnit\\Exception\\ReflectionException'
        );

        $this->findMethod($this, 'doesNotExist');
    }

    public function testFindMethod()
    {
        $this->assertInstanceOf(
            'ReflectionMethod',
            $this->findMethod($this, 'assertEquals')
        );
    }

    public function testFindPropertyNotExist()
    {
        $this->setExpectedException(
            'Deployer\\Component\\PHPUnit\\Exception\\ReflectionException'
        );

        $this->findProperty($this, 'doesNotExist');
    }

    public function testFindProperty()
    {
        $this->assertInstanceOf(
            'ReflectionProperty',
            $this->findProperty($this, 'count')
        );
    }

    public function testGetPropertyValue()
    {
        $this->assertSame(
            array(),
            $this->getPropertyValue($this, 'purgePaths')
        );
    }

    public function testPurgePathNotExist()
    {
        $this->setExpectedException(
            'Deployer\\Component\\PHPUnit\\Exception\\FileSystemException',
            'The path "/does/not/exist" does not exist.'
        );

        $this->purgePath('/does/not/exist');
    }

    // public function testPurgePathOpendirError()

    // public function testPurgePathRmdirError()

    // public function testPurgePathUnlinkError()

    public function testPurgePath()
    {
        unlink($temp = tempnam(sys_get_temp_dir(), 'tst'));

        mkdir($temp);

        $this->copyPath(__DIR__ . '/example', $temp);
        $this->purgePath($temp);

        $this->assertFileNotExists("$temp/sub/two");
        $this->assertFileNotExists("$temp/one");
        $this->assertFileNotExists($temp);
    }

    public function testRunProcess()
    {
        $hello = $this->runProcess('php', '-r', 'echo \'Hello!\';');

        $this->assertEquals(
            'Hello!',
            trim($hello->getOutput())
        );
    }

    public function testSetPropertyValue()
    {
        $expected = array(
            '/does/not/exist'
        );

        $this->setPropertyValue($this, 'purgePaths', $expected);

        $this->assertEquals(
            $expected,
            $this->getPropertyValue($this, 'purgePaths')
        );
    }
}