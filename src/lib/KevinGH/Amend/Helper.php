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

    use InvalidArgumentException,
        KevinGH\Version\Version,
        LogicException,
        RuntimeException,
        Symfony\Component\Console\Helper\Helper as _Helper;

    /**
     * Provides most of the update functionality offered by Amend.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Helper extends _Helper
    {
        /**
         * The buffer size.
         *
         * @api
         * @type integer
         */
        const BUFFER_SIZE = 4096;

        /**
         * The GitHub request context.
         *
         * @type array
         */
        private $context = array(
            'http' => array(
                'method' => 'GET',
                'header' => 'Accept: application/vnd.github.v3+json'
            )
        );

        /**
         * The cached downloads list.
         *
         * @type array
         */
        private $downloads;

        /**
         * The download version extract.
         *
         * @type callable
         */
        private $extract;

        /**
         * The download integrity checker.
         *
         * @type callable
         */
        private $integrity;

        /**
         * The major version lock state.
         *
         * @type boolean
         */
        private $lock = true;

        /**
         * The download matcher.
         *
         * @type callable
         */
        private $match;

        /**
         * The GitHub v3 API base URL.
         *
         * @type string
         */
        private $url;

        /**
         * The current application version.
         *
         * @type Version
         */
        private $version;

        /**
         * Returns the list of available downloads.
         *
         * @api
         * @return array The available downloads.
         */
        public function getDownloads()
        {
            if (null === $this->downloads)
            {
                if (null === $this->extract)
                {
                    throw new LogicException('No version extractor set.');
                }

                if (null === $this->match)
                {
                    throw new LogicException('No download matcher set.');
                }

                $this->downloads = array();

                foreach ($this->makeRequest('downloads') as $download)
                {
                    if (true === call_user_func($this->match, $download))
                    {
                        $download['version'] = new Version(
                            call_user_func($this->extract, $download)
                        );

                        $this->downloads[] = $download;
                    }
                }
            }

            return $this->downloads;
        }

        /**
         * Uses the download information to download its file.
         *
         * @api
         * @throws RuntimeException If the file could not be downloaded.
         * @param array $info The download information.
         * @param string $rename The name of the downloaded file.
         * @return string The temporary path to the file.
         */
        public function getFile(array $info, $rename = null)
        {
            unlink($dir = tempnam(sys_get_temp_dir(), 'ame'));

            mkdir($dir);

            $temp = $dir . DIRECTORY_SEPARATOR . ($rename ?: $info['name']);

            if (false === ($in = @ fopen($info['html_url'], 'rb')))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The download file "%s" could not be opened for reading: %s',
                    $info['html_url'],
                    $error['message']
                ));
            }

            if (false === ($out = @ fopen($temp, 'wb')))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The temporary file "%s" could not be opened for writing: %s',
                    $temp,
                    $error['message']
                ));
            }

            while (false === feof($in))
            {
                if (false === ($buffer = @ fread($in, self::BUFFER_SIZE)))
                {
                    $error = error_get_last();

                    fclose($in);
                    fclose($out);

                    throw new RuntimeException(sprintf(
                        'The download file "%s" could not be read: %s',
                        $info['html_url'],
                        $error['message']
                    ));
                }

                if (false === @ fwrite($out, $buffer))
                {
                    $error = error_get_last();

                    fclose($in);
                    fclose($out);

                    throw new RuntimeException(sprintf(
                        'The temporary file "%s" could not be written: %s',
                        $temp,
                        $error['message']
                    ));
                }
            }

            fclose($out);
            fclose($in);

            if ($this->integrity && (false === call_user_func($this->integrity, $temp)))
            {
                throw new RuntimeException(sprintf(
                    'The downloaded update "%s" failed the integrity check.',
                    $temp
                ));
            }

            return $temp;
        }

        /**
         * Returns the latest download from the list.
         *
         * @api
         * @param boolean $lock Lock to same major version?
         * @return array The latest download.
         */
        public function getLatest($lock = null)
        {
            if (null === $lock)
            {
                $lock = $this->lock;
            }

            if ($downloads = $this->getDownloads())
            {
                if (null === $this->version)
                {
                    throw new LogicException('No current version is set.');
                }

                $current = null;

                foreach ($downloads as $download)
                {
                    if ((null === $current)
                        || $download['version']->isGreaterThan($current['version']))
                    {
                        if ((false === $lock)
                            || ($this->version->getMajor() == $download['version']->getMajor()))
                        {
                            $current = $download;
                        }
                    }
                }

                return $current;
            }
        }

        /** {@inheritDoc} */
        public function getName()
        {
            return 'amend';
        }

        /**
         * Returns the processed application verison.
         *
         * @api
         * @return Version The application version.
         */
        public function getVersion()
        {
            return $this->version;
        }

        /**
         * Performs a request.
         *
         * @throws LogicException If the API URL is not set.
         * @param string $request The request.
         * @return array The response data.
         */
        public function makeRequest($request)
        {
            if (null === $this->url)
            {
                throw new LogicException('The API URL is not set.');
            }

            $request = $this->url . "/$request";

            if (false === ($string = @ file_get_contents(
                $request,
                false,
                stream_context_create($this->context)
            )))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The request "%s" could not be made: %s',
                    $request,
                    $error['message']
                ));
            }

            if (null === ($data = json_decode($string, true)))
            {
                if (JSON_ERROR_NONE === ($code = json_last_error()))
                {
                    throw new RuntimeException(sprintf(
                        'The request "%s" resulted in a null response.',
                        $request
                    ));
                }

                else
                {
                    throw new RuntimeException(sprintf(
                        'The request "%s" returned an invalid response [%d].',
                        $request,
                        $code
                    ));
                }
            }

            if (isset($data['message']))
            {
                throw new RuntimeException(sprintf(
                    'API request error for "%s": %s',
                    $request,
                    $data['message']
                ));
            }

            return $data;
        }

        /**
         * Sets the download version extractor callable.
         *
         * @api
         * @throws InvalidArgumentException If it is not a callable.
         * @param callable $extract The exctractor.
         */
        public function setExtractor($extract = null)
        {
            if ((null !== $extract) && (false === is_callable($extract)))
            {
                throw new InvalidArgumentException('The extractor is not callable.');
            }

            $this->extract = $extract;
        }

        /**
         * Sets the download integrity checker callable.
         *
         * @api
         * @throws InvalidArgumentException If it is not a callable.
         * @param callable $integrity The integrity checker.
         */
        public function setIntegrityChecker($integrity = null)
        {
            if ((null !== $integrity) && (false === is_callable($integrity)))
            {
                throw new InvalidArgumentException('The integrity checker is not callable.');
            }

            $this->integrity = $integrity;
        }

        /**
         * Sets the major version lock state.
         *
         * @api
         * @param boolean $lock The new state.
         */
        public function setLock($lock)
        {
            $this->lock = (bool) $lock;
        }

        /**
         * Sets the download matcher callable.
         *
         * @api
         * @throws InvalidArgumentException If it is not a callable.
         * @param callable $match The matcher.
         */
        public function setMatcher($match = null)
        {
            if ((null !== $match) && (false === is_callable($match)))
            {
                throw new InvalidArgumentException('The matcher is not callable.');
            }

            $this->match = $match;
        }

        /**
         * Sets the API base URL.
         *
         * @api
         * @param string $url The base URL.
         */
        public function setURL($url)
        {
            $this->url = $url;
        }

        /**
         * Sets the application version.
         *
         * @api
         * @param string|Version $version The version.
         */
        public function setVersion($version)
        {
            if (false === ($version instanceof Version))
            {
                $version = new Version($version);
            }

            $this->version = $version;
        }
    }