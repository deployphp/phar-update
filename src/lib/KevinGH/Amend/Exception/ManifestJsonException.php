<?php

namespace KevinGH\Amend\Exception;

use UnexpectedValueException;

/**
 * This exception is thrown if there is an issue with the data in the manifest
 * file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ManifestJsonException extends UnexpectedValueException
{
    /**
     * The URL to the manifest file.
     *
     * @var string
     */
    private $url;

    /**
     * Sets the manifest file URL and JSON error code.
     *
     * @param string  $url  The manifest file URL.
     * @param integer $code The JSON error code.
     */
    public function __construct($url, $code)
    {
        $this->url = $url;

        parent::__construct(null, $code);
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