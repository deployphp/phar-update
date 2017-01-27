<?php
use Deployer\Component\PharUpdate\Console\Command;
use Deployer\Component\PharUpdate\Console\Helper;
use Deployer\Component\PHPUnit\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends TestCase
{
    /**
     * @var Command
     */
    private $command;

    public function testSetManifestUri()
    {
        $this->command->setManifestUri('http://example.com/test.json');

        $this->assertEquals(
            'http://example.com/test.json',
            $this->getPropertyValue($this->command, 'manifestUri')
        );
    }

    public function testConfigure()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('redo'));
        $this->assertTrue($definition->hasOption('upgrade'));
    }

    public function testConfigureDisabled()
    {
        $command = new Command('upgrade', true);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('redo'));
        $this->assertFalse($definition->hasOption('upgrade'));
    }

    public function testExecuteNoManifest()
    {
        $app = new Application('Test', '1.0.0');
        $app->getHelperSet()->set(new Helper());
        $app->add(new Command('upgrade'));

        $tester = new CommandTester($app->get('upgrade'));

        $this->setExpectedException(
            'LogicException',
            'No manifest URI has been configured.'
        );

        $tester->execute(array('command' => 'upgrade'));
    }

    public function testExecute()
    {
        $_SERVER['argv'][0] = $this->createPhar('a.phar', 'alpha');

        $b = $this->createPhar('b.phar', 'beta');

        $manifest = $this->createFile();

        file_put_contents($manifest, json_encode(array(
            array(
                'name' => 'a.phar',
                'sha1' => sha1_file($b),
                'url' => $b,
                'version' => '1.2.0'
            ),
            array(
                'name' => 'a.phar',
                'sha1' => 'abcdef0123abcdef0123abcdef0123abcdef0123',
                'url' => 'file:///does/not/exist',
                'version' => '2.0.0'
            )
        )));

        $command = new Command('upgrade', true);
        $command->setManifestUri($manifest);

        $app = new Application('Test', '1.0.0');
        $app->getHelperSet()->set(new Helper());
        $app->add($command);

        $tester = new CommandTester($app->get('upgrade'));
        $tester->execute(array('command' => 'upgrade'));

        $this->assertRegExp(
            '/Update successful!/',
            $tester->getDisplay()
        );

        $this->assertEquals(
            'beta',
            exec('php ' . escapeshellarg($_SERVER['argv'][0]))
        );
    }

    public function testExecuteCurrent()
    {
        $manifest = $this->createFile();

        file_put_contents($manifest, '[]');

        $command = new Command('upgrade', true);
        $command->setManifestUri($manifest);

        $app = new Application('Test', '1.0.0');
        $app->getHelperSet()->set(new Helper());
        $app->add($command);

        $tester = new CommandTester($app->get('upgrade'));
        $tester->execute(array('command' => 'upgrade'));

        $this->assertRegExp(
            '/Already up-to-date\./',
            $tester->getDisplay()
        );
    }

    protected function createPhar($name, $echo)
    {
        unlink($file = $this->createFile($name));

        $phar = new Phar($file);
        $phar->addFromString(
            'index.php',
            '<?php echo ' . var_export($echo, true) . ';'
        );
        $phar->setStub($phar->createDefaultStub('index.php'));

        unset($box);

        return $file;
    }

    protected function setUp()
    {
        $this->command = new Command('upgrade');
    }
}
