<?php

namespace KevinGH\Amend\Exception;

use RuntimeException;

/**
 * This exception is thrown if there is a problem reading the manifest file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ManifestReadException extends RuntimeException
{
    /**
     * The URL to the manifest file.
     *
     * @var string
     */
    private $url;

    /**
     * Sets the manifest file URL and message.
     *
     * @param string $url     The manifest file URL.
     * @param string $message The message.
     */
    public function __construct($url, $message)
    {
        $this->url = $url;

        parent::__construct($message);
    }

    /**
     * Returns the URL to the manifest file.
     *
     * @return string The manifest file URL.
     */
    public function getUrl()
    {
        return $this->url;
    }
}