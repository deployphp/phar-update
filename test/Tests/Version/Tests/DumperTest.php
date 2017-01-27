<?php

namespace Deployer\Component\Version\Tests;

use Deployer\Component\PHPUnit\TestCase;
use Deployer\Component\Version\Dumper;
use Deployer\Component\Version\Parser;
use Deployer\Component\Version\Version;

class DumperTest extends TestCase
{
    /**
     * @var Version
     */
    private $version;

    public function testToComponents()
    {
        $this->assertSame(
            array(
                Parser::MAJOR => 1,
                Parser::MINOR => 2,
                Parser::PATCH => 3,
                Parser::PRE_RELEASE => array('pre', '1'),
                Parser::BUILD => array('build', '1'),
            ),
            Dumper::toComponents($this->version)
        );
    }

    public function testToString()
    {
        $this->assertEquals(
            '1.2.3-pre.1+build.1',
            Dumper::toString($this->version)
        );
    }

    protected function setUp()
    {
        $this->version = new Version(
            1,
            2,
            3,
            array('pre', '1'),
            array('build', '1')
        );
    }
}
