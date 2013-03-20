<?php

namespace KevinGH\Amend;

use KevinGH\Amend\Helper;
use LogicException;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manages updating or upgrading the Phar.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Command extends Base
{
    /**
     * Disable the ability to upgrade?
     *
     * @var boolean
     */
    private $disableUpgrade = false;

    /**
     * The manifest file URI.
     *
     * @var string
     */
    private $manifestUri;

    /**
     * {@inheritDoc}
     *
     * @param string  $name    The command name.
     * @param boolean $disable Disable upgrading?
     */
    public function __construct($name, $disable = false)
    {
        $this->disableUpgrade = $disable;

        parent::__construct($name);
    }

    /**
     * Sets the manifest URI.
     *
     * @param string $uri The URI.
     */
    public function setManifestUri($uri)
    {
        $this->manifestUri = $uri;
    }

    /**
     * @override
     */
    protected function configure()
    {
        $this->setDescription('Updates the application.');
        $this->addOption(
            'pre',
            'p',
            InputOption::VALUE_NONE,
            'Allow pre-release updates.'
        );
        $this->addOption(
            'redo',
            'r',
            InputOption::VALUE_NONE,
            'Redownload update if already using current version.'
        );

        if (false === $this->disableUpgrade) {
            $this->addOption(
                'upgrade',
                'u',
                InputOption::VALUE_NONE,
                'Upgrade to next major release, if available.'
            );
        }
    }

    /**
     * @override
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->manifestUri) {
            throw new LogicException(
                'No manifest URI has been configured.'
            );
        }

        $output->writeln('Looking for updates...');

        /** @var $amend Helper */
        $amend = $this->getHelper('amend');
        $manager = $amend->getManager($this->manifestUri);

        if ($manager->update(
            $this->getApplication()->getVersion(),
            $this->disableUpgrade ?: (false === $input->getOption('upgrade')),
            $input->getOption('pre')
        )){
            $output->writeln('<info>Update successful!</info>');
        } else {
            $output->writeln('<comment>Already up-to-date.</comment>');
        }
    }
}