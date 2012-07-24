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

    use Exception,
        Mock\Command,
        Symfony\Component\Console\Application,
        Symfony\Component\Console\Command\Command as _Command,
        Symfony\Component\Console\Tester\CommandTester;

    class CommandTest extends TestCase
    {
        const NAME = 'update';

        /** @type Application */
        private $app;

        /** @type string */
        private $bin;

        /** @type _Command */
        private $command;

        /** @type Helper */
        private $helper;

        /** @type string */
        private $original;

        /** @type CommandTester */
        private $tester;

        protected function setUp()
        {
            $this->app = new Application;

            $this->app->setVersion('1.0.0');

            $this->app->getHelperSet()->set($this->helper = new Helper);

            $this->app->add($this->command = new Command (self::NAME));

            $this->original = $_SERVER['argv'][0];

            $this->bin = $_SERVER['argv'][0] = $this->file();

            $this->tester = new CommandTester($this->command);
        }

        protected function tearDown()
        {
            $_SERVER['argv'][0] = $this->original;
        }

        public function testExecuteNoUpdates()
        {
            $url = $this->property($this->command, 'url');

            $url('file://' . $dir = $this->dir());

            file_put_contents("$dir/downloads", '[]');

            $this->assertEquals(1, $this->tester->execute(array(
                'command' => self::NAME
            )));

            $this->assertEquals(
                "No updates could be found.\n",
                $this->tester->getDisplay()
            );
        }

        public function testExecuteAlreadyCurrent()
        {
            $url = $this->property($this->command, 'url');

            $url('file://' . $dir = $this->dir());

            file_put_contents("$dir/downloads", utf8_encode(json_encode(array(
                array('name' => 'test-1.0.0.phar')
            ))));

            $this->tester->execute(array(
                'command' => self::NAME
            ));

            $this->assertEquals(
                "Already up-to-date.\n",
                $this->tester->getDisplay()
            );
        }

        public function testExecuteRedo()
        {
            $url = $this->property($this->command, 'url');

            $url('file://' . $dir = $this->dir());

            file_put_contents("$dir/downloads", utf8_encode(json_encode(array(
                array(
                    'name' => 'test-1.0.0.phar',
                    'html_url' => $this->file()
                )
            ))));

            $this->tester->execute(array(
                'command' => self::NAME,
                '--redo' => true
            ));

            $this->assertEquals(
                "Update successful!\n",
                $this->tester->getDisplay()
            );
        }

        public function testExecuteUpdate()
        {
            $url = $this->property($this->command, 'url');

            $url('file://' . $dir = $this->dir());

            file_put_contents("$dir/downloads", utf8_encode(json_encode(array(
                array(
                    'name' => 'test-1.0.1.phar',
                    'html_url' => $this->file('1')
                ),
                array(
                    'name' => 'test-2.0.0.phar',
                    'html_url' => $this->file('2')
                )
            ))));

            chmod($_SERVER['argv'][0], 0755);

            $this->tester->execute(array(
                'command' => self::NAME
            ));

            $this->assertEquals(
                "Update successful!\n",
                $this->tester->getDisplay()
            );

            $this->assertEquals(0755, fileperms($_SERVER['argv'][0]) & 511);

            $this->assertEquals('1', file_get_contents($_SERVER['argv'][0]));
        }

        public function testExecuteUpgrade()
        {
            $url = $this->property($this->command, 'url');

            $url('file://' . $dir = $this->dir());

            $lock = $this->property($this->command, 'lock');

            $lock(false);

            file_put_contents("$dir/downloads", utf8_encode(json_encode(array(
                array(
                    'name' => 'test-1.0.1.phar',
                    'html_url' => $this->file('1')
                ),
                array(
                    'name' => 'test-2.0.0.phar',
                    'html_url' => $this->file('2')
                )
            ))));

            $this->tester->execute(array(
                'command' => self::NAME
            ));

            $this->assertEquals(
                "Update successful!\n",
                $this->tester->getDisplay()
            );

            $this->assertEquals('2', file_get_contents($_SERVER['argv'][0]));
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage Unable to replace with update
         */
        public function testReplaceRenameFail()
        {
            $method = $this->method($this->command, 'replace');

            $method('/does/not/exist');
        }

        /**
         * @expectedException RuntimeException
         * @expectedExceptionMessage Unable to copy permissions
         */
        public function testReplaceChmodFail()
        {
            if ($this->redeclare('chmod', '', 'return false;'))
            {
                return;
            }

            try
            {
                $method = $this->method($this->command, 'replace');

                $method($this->file());
            }

            catch (Exception $e)
            {
            }

            $this->restore('chmod');

            if (isset($e)) throw $e;
        }
    }