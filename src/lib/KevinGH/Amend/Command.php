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

    use RuntimeException,
        Symfony\Component\Console\Command\Command as _Command,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Input\InputOption,
        Symfony\Component\Console\Output\OutputInterface;

    /**
     * Uses the helper to update the application.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Command extends _Command
    {
        /**
         * The version extraction regular expression.
         *
         * @api
         * @type string
         */
        protected $extract;

        /**
         * The integrity checker callable.
         *
         * @api
         * @type callable
         */
        protected $integrity;

        /**
         * The major version force lock flag.
         *
         * @api
         * @type boolean
         */
        protected $lock = null;

        /**
         * The download matching regular expression.
         *
         * @api
         * @type string
         */
        protected $match;

        /**
         * The name of the temporary file.
         *
         * @api
         * @type string
         */
        protected $rename = null;

        /**
         * The API base URL.
         *
         * @api
         * @type string
         */
        protected $url;

        /** {@inheritDoc} */
        public function configure()
        {
            $this->setDescription('Updates or upgrades the application.');

            if (null === $this->lock)
            {
                $this->addOption(
                    'upgrade',
                    'u',
                    InputOption::VALUE_NONE,
                    'Upgrade if next major release is available.'
                );
            }

            $this->addOption(
                'redo',
                'r',
                InputOption::VALUE_NONE,
                'Redownload if already current version.'
            );
        }

        /** {@inheritDoc} */
        public function execute(InputInterface $input, OutputInterface $output)
        {
            $helper = $this->prepare($input);

            if ($info = $helper->getLatest($this->lock))
            {
                $current = $helper->getVersion();

                if ($input->getOption('redo') || $info['version']->isGreaterThan($current))
                {
                    $temp = $helper->getFile($info, $this->rename);

                    $this->replace($temp);

                    $output->writeln('<info>Update successful!</info>');
                }

                else
                {
                    $output->writeln('<info>Already up-to-date.</info>');
                }
            }

            else
            {
                $output->writeln('<comment>No updates could be found.</comment>');

                return 1;
            }
        }

        /**
         * Prepares the helper for the actual update/upgrade process.
         *
         * @api
         * @param InputInterface $input The input.
         * @return Helper The prepared Amend helper.
         */
        protected function prepare(InputInterface $input)
        {
            $helper = $this->getHelper('amend');

            if ($regex = $this->extract)
            {
                $helper->setExtractor(function ($info) use ($regex)
                {
                    return preg_replace($regex, '\\1', $info['name']);
                });
            }

            $helper->setIntegrityChecker($this->integrity);

            $helper->setLock(false === $input->getOption('upgrade'));

            if ($regex = $this->match)
            {
                $helper->setMatcher(function ($info) use ($regex)
                {
                    return (bool) preg_match($regex, $info['name']);
                });
            }

            $helper->setURL($this->url);

            $helper->setVersion($this->getApplication()->getVersion());

            return $helper;
        }

        /**
         * Replaces the current running program with the update.
         *
         * @api
         * @param string $temp The path to the update file.
         */
        protected function replace($temp)
        {
            $perms = fileperms($_SERVER['argv'][0]) & 511;

            if (false === @ rename($temp, $_SERVER['argv'][0]))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'Unable to replace with update "%s": %s',
                    $temp,
                    $error['message']
                ));
            }

            if (false === @ chmod($_SERVER['argv'][0], $perms))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'Unable to copy permissions: %s',
                    $error['message']
                ));
            }
        }
    }