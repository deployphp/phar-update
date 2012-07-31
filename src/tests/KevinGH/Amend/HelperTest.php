<?php

/* This file is part of Amend.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Amend;

use Exception;
use KevinGH\Version\Version;

class HelperTest extends TestCase
{
    private $helper;

    protected function setUp()
    {
        $this->helper = new Helper;
    }

    public function testGetDownloads()
    {
        $this->helper->setExtractor(
            function ($download) {
                return preg_replace('/^test\-(.+?)\.txt$/', '\\1', $download['name']);
            }
        );

        $this->helper->setMatcher(
            function ($download) {
                return (bool) preg_match('/^test\-(.+?)\.txt$/', $download['name']);
            }
        );

        $this->helper->setURL($dir = $this->dir());

        file_put_contents("$dir/downloads", utf8_encode(json_encode($expected = array(
            array('name' => 'test-1.0.0.txt'),
            array('name' => 'test-1.0.1.txt')
        ))));

        $downloads = $this->helper->getDownloads();

        $expected = array(
            array(
                'name' => 'test-1.0.0.txt',
                'version' => new Version('1.0.0')
            ),
            array(
                'name' => 'test-1.0.1.txt',
                'version' => new Version('1.0.1')
            )
        );

        $this->assertEquals($expected[0], $downloads[0]);
        $this->assertEquals($expected[1], $downloads[1]);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No version extractor set.
     */
    public function testGetDownloadsNoExtractor()
    {
        $this->helper->getDownloads();
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No download matcher set.
     */
    public function testGetDownloadsNoMatcher()
    {
        $this->helper->setExtractor(
            function () {
            }
        );

        $this->helper->getDownloads();
    }

    public function testGetFile()
    {
        $file = $this->file($rand = rand());

        $temp = $this->helper->getFile(array(
            'html_url' => $file,
            'name' => 'other-test'
        ), 'test');

        $this->helper->setIntegrityChecker(
            function ($temp) use ($rand) {
                if ($rand != file_get_contents($temp)) {
                    return false;
                }
            }
        );

        $this->assertFileEquals($file, $temp);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The download file
     */
    public function testGetFileInOpenFail()
    {
        if ($this->redeclare('fopen', '', 'return false;')) {
            return;
        }

        try {
            $this->helper->getFile(array(
                'html_url' => 'file:///test/path',
                'name' => 'test'
            ));
        } catch (Exception $e) {
        }

        $this->restore('fopen');

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The temporary file
     */
    public function testGetFileOutOpenFail()
    {
        if ($this->redeclare(
            'fopen',
            '$a, $b',
            'if ("wb" == $b) return false; return _fopen($a, $b);')
        ) {
            return;
        }

        try {
            $this->helper->getFile(array(
                'html_url' => 'file://' . $this->file(),
                'name' => 'test'
            ));
        } catch (Exception $e) {
        }

        $this->restore('fopen');

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The download file
     */
    public function testGetFileReadFail()
    {
        if ($this->redeclare('fread', '', 'return false;')) {
            return;
        }

        try {
            $this->helper->getFile(array(
                'html_url' => 'file://' . $this->file(),
                'name' => 'test'
            ));
        } catch (Exception $e) {
        }

        $this->restore('fread');

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The temporary file
     */
    public function testGetFileWriteFail()
    {
        if ($this->redeclare('fwrite', '', 'return false;')) {
            return;
        }

        try {
            $this->helper->getFile(array(
                'html_url' => 'file://' . $this->file(),
                'name' => 'test'
            ));
        } catch (Exception $e) {
        }

        $this->restore('fwrite');

        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The downloaded update
     */
    public function testGetFileIntegrityFail()
    {
        $file = $this->file($rand = rand());

        $this->helper->setIntegrityChecker(
            function ($temp) use ($rand) {
                return false;
            }
        );

        $temp = $this->helper->getFile(array(
            'html_url' => $file,
            'name' => 'other-test'
        ), 'test');
    }

    public function testGetLatest()
    {
        $this->helper->setExtractor(
            function ($download) {
                return preg_replace('/^test\-(.+?)\.txt$/', '\\1', $download['name']);
            }
        );

        $this->helper->setMatcher(
            function ($download) {
                return (bool) preg_match('/^test\-(.+?)\.txt$/', $download['name']);
            }
        );

        $this->helper->setURL($dir = $this->dir());

        file_put_contents("$dir/downloads", utf8_encode(json_encode($expected = array(
            array('name' => 'test-1.0.0.txt'),
            array('name' => 'test-2.0.1.txt'),
            array('name' => 'test-1.0.1.txt')
        ))));

        $this->helper->setVersion('1.0.0');

        $latest = $this->helper->getLatest();

        $this->assertEquals('1.0.1', (string)$latest['version']);

        $latest = $this->helper->getLatest(false);

        $this->assertEquals('2.0.1', (string)$latest['version']);
    }

    public function testGetLatestEmpty()
    {
        $this->helper->setExtractor(
            function () {
            }
        );

        $this->helper->setMatcher(
            function () {
            }
        );

        $this->helper->setURL($dir = $this->dir());

        $this->helper->setVersion('1.0.0');

        file_put_contents("$dir/downloads", '{}');

        $this->assertNull($this->helper->getLatest(false));
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage No current version is set.
     */
    public function testGetLatestNoCurrent()
    {
        $this->helper->setExtractor(
            function () {
                return '1.0.0';
            }
        );

        $this->helper->setMatcher(
            function () {
                return true;
            }
        );

        $this->helper->setURL($dir = $this->dir());

        file_put_contents("$dir/downloads", '[{"name": ""}]');

        $this->helper->getLatest(false);
    }

    public function testGetName()
    {
        $this->assertEquals('amend', $this->helper->getName());
    }

    public function testGetVersion()
    {
        $this->assertNull($this->helper->getVersion());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The API URL is not set.
     */
    public function testMakeRequestNoUrl()
    {
        $this->helper->makeRequest('test');
    }

    public function testMakeRequest()
    {
        $file = $this->file(utf8_encode(json_encode($expected = array('rand' => rand()))));

        $this->helper->setURL('file://' . dirname($file));

        $this->assertSame($expected, $this->helper->makeRequest(basename($file)));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request "/does/not/exist" could not be made:
     */
    public function testMakeRequestFail()
    {
        $this->helper->setURL('/does/not');

        $this->helper->makeRequest('exist');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request
     */
    public function testMakeRequestIsNull()
    {
        $this->helper->setURL('file://' . dirname($file = $this->file('')));

        $this->helper->makeRequest(basename($file));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request
     */
    public function testMakeRequestIsInvalid()
    {
        $this->helper->setURL('file://' . dirname($file = $this->file('{')));

        $this->helper->makeRequest(basename($file));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage API request error for
     */
    public function testMakeRequestApiError()
    {
        $this->helper->setURL('file://' . dirname($file = $this->file('{"message": "test"}')));

        $this->helper->makeRequest(basename($file));
    }

    public function testSetExtractor()
    {
        $this->helper->setExtractor($expected = array(
            __CLASS__,
            __METHOD__
        ));

        $property = $this->property($this->helper, 'extract');

        $this->assertEquals($expected, $property());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The extractor is not callable.
     */
    public function testSetExtractorInvalid()
    {
        $this->helper->setExtractor(123);
    }

    public function testSetIntegrityChecker()
    {
        $this->helper->setIntegrityChecker($expected = array(
            __CLASS__,
            __METHOD__
        ));

        $property = $this->property($this->helper, 'integrity');

        $this->assertEquals($expected, $property());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The integrity checker is not callable.
     */
    public function testSetIntegrityCheckerInvalid()
    {
        $this->helper->setIntegrityChecker(123);
    }

    public function testSetLock()
    {
        $property = $this->property($this->helper, 'lock');

        $this->assertTrue($property());

        $this->helper->setLock(false);

        $this->assertFalse($property());
    }

    public function testSetMatcher()
    {
        $this->helper->setMatcher($expected = array(
            __CLASS__,
            __METHOD__
        ));

        $property = $this->property($this->helper, 'match');

        $this->assertEquals($expected, $property());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The matcher is not callable.
     */
    public function testSetMatcherInvalid()
    {
        $this->helper->setMatcher(123);
    }

    public function testSetUrl()
    {
        $this->helper->setURL('test');

        $property = $this->property($this->helper, 'url');

        $this->assertEquals('test', $property());
    }

    public function testSetVersion()
    {
        $this->helper->setVersion('1.0.0');

        $this->assertEquals('1.0.0', (string) $this->helper->getVersion());
    }
}

