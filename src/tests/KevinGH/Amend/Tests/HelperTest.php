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

use Herrera\PHPUNit\TestCase;
use KevinGH\Amend\Helper;

class HelperTest extends TestCase
{
    /**
     * @var Helper
     */
    private $helper;

    public function testGetManager()
    {
        $file = $this->createFile();

        file_put_contents($file, '[]');

        $this->assertInstanceOf(
            'Herrera\\Phar\\Update\\Manager',
            $this->helper->getManager($file)
        );
    }

    public function testGetName()
    {
        $this->assertEquals('amend', $this->helper->getName());
    }

    protected function setUp()
    {
        $this->helper = new Helper();
    }
}