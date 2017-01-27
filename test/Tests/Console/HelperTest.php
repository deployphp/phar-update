<?php
use Deployer\Component\PHPUnit\TestCase;
use Deployer\Component\PharUpdate\Console\Helper;

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
            'Deployer\\Component\\PharUpdate\\Manager',
            $this->helper->getManager($file)
        );
    }

    public function testGetName()
    {
        $this->assertEquals('phar-update', $this->helper->getName());
    }

    protected function setUp()
    {
        $this->helper = new Helper();
    }
}
