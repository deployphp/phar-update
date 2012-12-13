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

use KevinGH\Amend\Helper;
use KevinGH\Version\Version;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Uses the helper to update the application.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Command extends Base
{
    /**
     * The major version lock flag.
     *
     * @var boolean
     */
    private $lock;

    /**
     * The current application version.
     *
     * @var Version
     */
    private $version;

    /**
     * Sets the command name, major version lock flag, and current application
     * version if provided. The command name is required. By default, no lock
     * is used, and the current application version is parsed using Version.
     *
     * @param string  $name    The command name.
     * @param boolean $lock    Lock to current major version?
     * @param Version $version The current application version.
     */
    public function __construct(
        $name,
        $lock = null,
        Version $version = null
    ){
        $this->lock = $lock;
        $this->version = $version;

        parent::__construct($name);
    }

    /**
     * @override
     */
    protected function configure()
    {
        $this->setDescription('Updates the application.');

        if (null === $this->lock) {
            $this->addOption(
                'upgrade',
                'u',
                InputOption::VALUE_NONE,
                'Upgrade to next major release if available.'
            );
        }

        $this->addOption(
            'redo',
            'r',
            InputOption::VALUE_NONE,
            'Redownload if already current version.'
        );
    }

    /**
     * @override
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->version) {
            $this->version = Version::create(
                $this->getApplication()->getVersion()
            );
        }

        $helper = $this->getHelper(Helper::NAME);
        $manifest = $helper->getManifest();
        $update = $helper->findUpdate(
            $manifest,
            $this->version,
            (null === $this->lock)
                ? (false === $input->getOption('upgrade'))
                : $this->lock
        );

        if ($update) {
            if ((false === $input->getOption('redo'))
                && $update['version']->isEqualTo($this->version)) {
                $output->writeln('<info>Already up-to-date.</info>');
            } else {
                $helper->replaceFile(
                    $helper->downloadUpdate($update),
                    $helper->getRunningFile()
                );

                $output->writeln('Update successful!');
            }
        } else {
            $output->writeln('<comment>No updates could be found.</comment>');

            return 1;
        }
    }
}
