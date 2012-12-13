<?php

/* This file is part of Amend.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Amend\Tests;

use KevinGh\Amend\Exception\FileChecksumException;
use KevinGh\Amend\Exception\FileException;
use KevinGh\Amend\Exception\ManifestJsonException;
use KevinGh\Amend\Exception\ManifestReadException;
use KevinGH\Amend\Helper;
use KevinGH\Runkit\RunkitTestCase;
use KevinGH\Version\Version;
use PHPUnit_Framework_Error_Warning;

class HelperTest extends RunkitTestCase
{
    private $file;
    private $helper;
    private $manifest;
    private $sha1;
    private $updates;
    private $version;

    protected function setUp()
    {
        $this->manifest = tempnam(sys_get_temp_dir(), 'ame');
        $this->file = __FILE__;
        $this->sha1 = sha1_file($this->file);
        $this->updates = array(
            array(
                'name' => basename(__FILE__),
                'sha1' => $this->sha1,
                'url' => $this->file,
                'version' => '1.0.0'
            ),
            array(
                'name' => basename(__FILE__),
                'sha1' => $this->sha1,
                'url' => $this->file,
                'version' => '1.1.0'
            ),
            array(
                'name' => basename(__FILE__),
                'sha1' => $this->sha1,
                'url' => $this->file,
                'version' => '2.0.0'
            )
        );

        file_put_contents($this->manifest, json_encode($this->updates));

        foreach ($this->updates as $i => $update) {
            $this->updates[$i]['version'] = Version::create($update['version']);
        }

        $this->helper = new Helper($this->manifest);
        $this->version = new Version('1.0.0');
    }

    /**
     * @expectedException KevinGH\Amend\Exception\FileException
     * @expectedExceptionMessage Fake warning.
     */
    public function testDownlodUpdateSourceOpenError()
    {
        $this->requireRunkit(true);
        $this->redefineFunction(
            'fopen',
            '',
            'trigger_error("Fake warning.", E_USER_WARNING); return false;'
        );

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->downloadUpdate($this->updates[0]);
        } catch (FileException $exception) {
            $this->assertEquals(
                $this->updates[0]['url'],
                $exception->getProblemFile()
            );

            throw $exception;
        }
    }

    /**
     * @expectedException KevinGH\Amend\Exception\FileException
     * @expectedExceptionMessage Fake warning.
     */
    public function testDownlodUpdateNewOpenError()
    {
        $this->requireRunkit(true);
        $this->renameFunction('fopen', '_fopen');
        $this->addFunction(
            'fopen',
            '$a, $b',
            'if ($a != "' . $this->updates[0]['url'] . '") { trigger_error("Fake warning.", E_USER_WARNING); return false; } return _fopen($a, $b);'
        );

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->downloadUpdate($this->updates[0]);
        } catch (FileException $exception) {
            $this->assertStringStartsWith(
                sys_get_temp_dir(),
                $exception->getProblemFile()
            );

            throw $exception;
        }
    }

    /**
     * @expectedException KevinGH\Amend\Exception\FileException
     * @expectedExceptionMessage Fake warning.
     */
    public function testDownlodUpdateSourceReadError()
    {
        $this->requireRunkit(true);
        $this->redefineFunction(
            'fread',
            '',
            'trigger_error("Fake warning.", E_USER_WARNING); return false;'
        );

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->downloadUpdate($this->updates[0]);
        } catch (FileException $exception) {
            $this->assertEquals(
                $this->updates[0]['url'],
                $exception->getProblemFile()
            );

            throw $exception;
        }
    }

    /**
     * @expectedException KevinGH\Amend\Exception\FileException
     * @expectedExceptionMessage Fake warning.
     */
    public function testDownlodUpdateNewWriteError()
    {
        $this->requireRunkit(true);
        $this->redefineFunction(
            'fwrite',
            '',
            'trigger_error("Fake warning.", E_USER_WARNING); return false;'
        );

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->downloadUpdate($this->updates[0]);
        } catch (FileException $exception) {
            $this->assertStringStartsWith(
                sys_get_temp_dir(),
                $exception->getProblemFile()
            );

            throw $exception;
        }
    }

    /**
     * @expectedException KevinGH\Amend\Exception\FileChecksumException
     */
    public function testDownlodUpdateChecksumError()
    {
        $this->requireRunkit(true);
        $this->redefineFunction(
            'sha1_file',
            '',
            'return "fake checksum";'
        );

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->downloadUpdate($this->updates[0]);
        } catch (FileChecksumException $exception) {
            $this->assertEquals('fake checksum', $exception->getNewChecksum());
            $this->assertStringStartsWith(
                sys_get_temp_dir(),
                $exception->getNewPath()
            );
            $this->assertEquals(
                $this->updates[0]['sha1'],
                $exception->getSourceChecksum()
            );
            $this->assertEquals(
                $this->updates[0]['url'],
                $exception->getSourcePath()
            );

            throw $exception;
        }
    }

    public function testDownloadUpdate()
    {
        $temp = $this->helper->downloadUpdate($this->updates[0]);

        $this->assertFileEquals($this->file, $temp);
    }

    public function testFindUpdateMajorVersionLock()
    {
        $this->assertEquals(
            $this->updates[1],
            $this->helper->findUpdate($this->updates, $this->version, true)
        );
    }

    public function testFindUpdate()
    {
        $this->assertEquals(
            $this->updates[2],
            $this->helper->findUpdate($this->updates, $this->version)
        );
    }

    /**
     * @expectedException KevinGH\Amend\Exception\ManifestReadException
     * @expectedExceptionMessage Fake warning.
     */
    public function testGetManifestReadError()
    {
        $this->requireRunkit(true);
        $this->redefineFunction(
            'file_get_contents',
            '',
            'trigger_error("Fake warning.", E_USER_WARNING); return false;'
        );

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->getManifest();
        } catch (ManifestReadException $exception) {
            $this->assertEquals($this->manifest, $exception->getUrl());

            throw $exception;
        }
    }

    /**
     * @expectedException KevinGH\Amend\Exception\ManifestJsonException
     */
    public function testGetManifestJsonError()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ame');

        file_put_contents($tmp, '{');

        $helper = new Helper($tmp);

        try {
            $helper->getManifest();
        } catch (ManifestJsonException $exception) {
            $this->assertEquals(JSON_ERROR_SYNTAX, $exception->getCode());
            $this->assertEquals($tmp, $exception->getUrl());

            throw $exception;
        }
    }

    public function testGetManifest()
    {
        $this->assertEquals($this->updates, $this->helper->getManifest());
    }

    public function testGetName()
    {
        $this->assertEquals(Helper::NAME, $this->helper->getName());
    }

    public function testGetRunningFile()
    {
        $this->assertEquals(
            realpath($_SERVER['argv'][0]),
            $this->helper->getRunningFile()
        );
    }

    /**
     * @expectedException KevinGH\Amend\Exception\FileException
     * @expectedExceptionMessage Fake warning.
     */
    public function testReplaceRenameError()
    {
        $this->requireRunkit(true);
        $this->redefineFunction(
            'rename',
            '',
            'trigger_error("Fake warning.", E_USER_WARNING); return false;'
        );

        $tmp1 = tempnam(sys_get_temp_dir(), 'ame');
        $tmp2 = tempnam(sys_get_temp_dir(), 'ame');

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->replaceFile($tmp1, $tmp2);
        } catch (FileException $exception) {
            $this->assertEquals("$tmp1 -> $tmp2", $exception->getProblemFile());

            throw $exception;
        }
    }

    /**
     * @expectedException KevinGH\Amend\Exception\FileException
     * @expectedExceptionMessage Fake warning.
     */
    public function testReplaceChmodError()
    {
        $this->requireRunkit(true);
        $this->redefineFunction(
            'chmod',
            '',
            'trigger_error("Fake warning.", E_USER_WARNING); return false;'
        );

        $tmp1 = tempnam(sys_get_temp_dir(), 'ame');
        $tmp2 = tempnam(sys_get_temp_dir(), 'ame');

        PHPUnit_Framework_Error_Warning::$enabled = false;

        try {
            $this->helper->replaceFile($tmp1, $tmp2);
        } catch (FileException $exception) {
            $this->assertEquals("$tmp1 -> $tmp2", $exception->getProblemFile());

            throw $exception;
        }
    }

    public function testReplaceFile()
    {
        $tmp1 = tempnam(sys_get_temp_dir(), 'ame');
        $tmp2 = tempnam(sys_get_temp_dir(), 'ame');

        if (false === @chmod($tmp2, 0777)) {
            $this->markTestSkipped('Could not chmod file.');
        }

        $this->helper->replaceFile($tmp1, $tmp2);

        $this->assertFalse(file_exists($tmp1));
        $this->assertEquals(0777, fileperms($tmp2) & 511);
    }
}
