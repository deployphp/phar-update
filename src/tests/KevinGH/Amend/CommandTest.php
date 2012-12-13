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

use KevinGH\Amend\Command;
use KevinGH\Amend\Helper;
use KevinGH\Runkit\RunkitTestCase;
use KevinGH\Version\Version;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends RunkitTestCase
{
    private $arg0;
    private $app;
    private $command;
    private $manifest;
    private $updates;
    private $tester;

    protected function tearDown()
    {
        parent::tearDown();

        $_SERVER['argv'][0] = $this->arg0;
    }

    protected function setUp()
    {
        $this->arg0 = $_SERVER['argv'][0];
        $_SERVER['argv'][0] = tempnam(sys_get_temp_dir(), 'ame');

        file_put_contents($_SERVER['argv'][0], '0');

        $t1 = tempnam(sys_get_temp_dir(), 'ame');
        $t2 = tempnam(sys_get_temp_dir(), 'ame');
        $t3 = tempnam(sys_get_temp_dir(), 'ame');

        file_put_contents($t1, '1');
        file_put_contents($t2, '2');
        file_put_contents($t3, '3');

        $this->updates = array(
            array(
                'name' => 'abc',
                'sha1' => sha1_file($t1),
                'url' => $t1,
                'version' => '1.0.0'
            ),
            array(
                'name' => 'def',
                'sha1' => sha1_file($t2),
                'url' => $t2,
                'version' => '1.1.0'
            ),
            array(
                'name' => 'ghi',
                'sha1' => sha1_file($t3),
                'url' => $t3,
                'version' => '2.0.0'
            )
        );

        $this->manifest = tempnam(sys_get_temp_dir(), 'ame');

        file_put_contents($this->manifest, json_encode($this->updates));

        foreach ($this->updates as $i => $update) {
            $this->updates[$i]['version'] = Version::create($update['version']);
        }

        $helper = new Helper($this->manifest);
        $this->app = new Application('Test App', '1.1.0');
        $this->command = new Command('update');
        $this->tester = new CommandTester($this->command);

        $this->app->add($this->command);
        $this->app->getHelperSet()->set($helper);
    }

    public function testConfigure()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('upgrade'));
        $this->assertTrue($definition->hasOption('redo'));
    }

    public function testConfigureLocked()
    {
        $this->command = new Command('update', true);
        $definition = $this->command->getDefinition();

        $this->assertFalse($definition->hasOption('upgrade'));
        $this->assertTrue($definition->hasOption('redo'));
    }

    public function testExecute()
    {
        $this->tester->execute(array('command' => 'update'));

        $this->assertEquals('0', file_get_contents($_SERVER['argv'][0]));
        $this->assertEquals(
            'Already up-to-date.',
            trim($this->tester->getDisplay())
        );
    }

    public function testExecuteRedo()
    {
        $this->tester->execute(array(
            'command' => 'update',
            '--redo' => true
        ));

        $this->assertEquals('2', file_get_contents($_SERVER['argv'][0]));
        $this->assertEquals(
            'Update successful!',
            trim($this->tester->getDisplay())
        );
    }

    public function testExecuteUpgrade()
    {
        $this->tester->execute(array(
            'command' => 'update',
            '--upgrade' => true
        ));

        $this->assertEquals('3', file_get_contents($_SERVER['argv'][0]));
        $this->assertEquals(
            'Update successful!',
            trim($this->tester->getDisplay())
        );
    }

    public function testExecuteEmptyManifest()
    {
        file_put_contents($this->manifest, '[]');

        $this->assertEquals(
            1,
            $this->tester->execute(array('command' => 'update'))
        );

        $this->assertEquals('0', file_get_contents($_SERVER['argv'][0]));
        $this->assertEquals(
            'No updates could be found.',
            trim($this->tester->getDisplay())
        );
    }
}
