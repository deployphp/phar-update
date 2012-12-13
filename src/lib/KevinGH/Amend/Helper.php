<?php

namespace KevinGH\Amend;

use KevinGH\Amend\Exception\FileChecksumException;
use KevinGH\Amend\Exception\FileException;
use KevinGH\Amend\Exception\ManifestJsonException;
use KevinGH\Amend\Exception\ManifestReadException;
use KevinGH\Version\Version;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper as Base;

/**
 * Manages updating the PHAR application using a local or remote manifest file.
 *
 * Manifest file example:
 * <code>
 * [
 *     {
 *         "name": "box.phar",
 *         "sha1": "6b6eab3b25eec36cc34c471798cae04f02e3179e",
 *         "url": "https://github.com/downloads/kherge/Box/box-1.0.3.phar",
 *         "version": "1.0.3"
 *     },
 *     {
 *         "name": "box.phar",
 *         "sha1": "86603190e1fa93af608bbcd96e658118b6a5391f",
 *         "url": "http://box-project.org/box-1.0.4.phar",
 *         "version": "1.0.4"
 *     }
 * ]
 * </code>
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Helper extends Base
{
    /**
     * The name of the helper.
     *
     * @var string
     */
    const NAME = 'amend';

    /**
     * The download buffer size.
     *
     * @var integer
     */
    private $bufferSize;

    /**
     * The stream context.
     *
     * @var resource
     */
    private $context;

    /**
     * The name of the downloaded temporary file.
     *
     * @var string
     */
    private $name;

    /**
     * The manifest file URL.
     *
     * @var string
     */
    private $url;

    /**
     * Sets the manifest file URL and creates the stream context.
     *
     * @param string  $url    The URL.
     * @param string  $name   The name of the downloaded temporary file.
     * @param integer $buffer The download buffer size.
     */
    public function __construct($url, $name = null, $buffer = 4096)
    {
        $this->bufferSize = $buffer;
        $this->context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'user_agent' => 'Amend/2.0'
            )
        ));

        $this->name = $name;
        $this->url = $url;
    }

    /**
     * Downloads the update file and validates its checksum.
     *
     * @param array $update The update.
     *
     * @return string The temporary file path.
     *
     * @throws FileException         If there is a file problem.
     * @throws FileChecksumException If the downloaded file is invalid.
     */
    public function downloadUpdate(array $update)
    {
        unlink($dir = tempnam(sys_get_temp_dir(), 'ame'));

        mkdir($dir);

        $temp = $dir . DIRECTORY_SEPARATOR . ($this->name ?: $update['name']);

        if (false === ($in = @ fopen($update['url'], 'rb'))) {
            throw FileException::create($update['url']);
        }

        if (false === ($out = @ fopen($temp, 'wb'))) {
            throw FileException::create($temp);
        }

        while (false === feof($in)) {
            if (false === ($buffer = @ fread($in, $this->bufferSize))) {
                fclose($in);
                fclose($out);

                throw FileException::create($update['url']);
            }

            if (false === @ fwrite($out, $buffer)) {
                fclose($in);
                fclose($out);

                throw FileException::create($temp);
            }
        }

        fclose($out);
        fclose($in);

        if (($checksum = sha1_file($temp)) !== $update['sha1']) {
            throw new FileChecksumException(
                $update['url'],
                $update['sha1'],
                $temp,
                $checksum
            );
        }

        return $temp;
    }

    /**
     * Finds an update for the given version.
     *
     * @param array   $updates The list of updates.
     * @param Version $version The version.
     * @param boolean $lock    Lock to major version?
     *
     * @return array The update.
     */
    public function findUpdate(array $updates, Version $version, $lock = false)
    {
        $latest = null;

        foreach ($updates as $update) {
            if ($lock
                && ($update['version']->getMajor() !== $version->getMajor())) {
                continue;
            }

            $test = $latest ? $latest['version'] : $version;

            if (0 >= $update['version']->compareTo($test)) {
                $latest = $update;
            }
        }

        return $latest;
    }

    /**
     * Retrieves the manifest and returns its contents. The value of the
     * "version" key will be converted into an instance of the Version
     * class.
     *
     * @return array The manifest.
     *
     * @throws ManifestJsonException If there is a JSON encoding issue with the
     *                               manifest's data.
     * @throws ManifestReadException If there was a problem reading the manifest
     *                               file from the URL.
     */
    public function getManifest()
    {
        if (false === ($manifest = @ file_get_contents(
            $this->url,
            false,
            $this->context
        ))){
            $error = error_get_last();

            throw new ManifestReadException(
                $this->url,
                $error ? $error['message'] : 'Unknown reason.'
            );
        }

        $manifest = json_decode($manifest, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ManifestJsonException(
                $this->url,
                json_last_error()
            );
        }

        foreach ($manifest as $i => $update) {
            $manifest[$i]['version'] = Version::create($update['version']);
        }

        return $manifest;
    }

    /**
     * @override
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * Returns the canonical file path for the running program.
     *
     * @return string The canonical file path.
     */
    public function getRunningFile()
    {
        return realpath($_SERVER['argv'][0]);
    }

    /**
     * Replaces a file with another one, preserving permissions.
     *
     * @param string $from The file to replace with.
     * @param stirng $to   The file to replace.
     *
     * @throws FileException If the file could not be replaced, or if the
     *                       permissions could not be preserved.
     */
    public function replaceFile($from, $to)
    {
        $perms = fileperms($to) & 511;

        if (false === @ rename($from, $to)) {
            throw FileException::create("$from -> $to");
        }

        if (false === @ chmod($to, $perms)) {
            throw FileException::create("$from -> $to");
        }
    }
}