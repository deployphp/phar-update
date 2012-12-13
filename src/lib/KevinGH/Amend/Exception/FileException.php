<?php

namespace KevinGH\Amend\Exception;

use RuntimeException;

/**
 * This exception is used when there is an issue with a file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class FileException extends RuntimeException
{
    /**
     * The problematic file path (or URL).
     *
     * @var string
     */
    private $problemFile;

    /**
     * Sets the problematic file path (or URL) and the error message.
     *
     * @param string $problemFile The problematic file path (or URL).
     * @param string $message     The error message.
     */
    public function __construct($problemFile, $message)
    {
        $this->problemFile = $problemFile;

        parent::__construct($message);
    }

    /**
     * Creates a new exception using the last error message.
     *
     * @param string $problemFile The problematic file path (or URL).
     *
     * @return FileException A new exception.
     */
    public static function create($problemFile)
    {
        $error = error_get_last();

        return new static(
            $problemFile,
            $error ? $error['message'] : 'Unknown error.'
        );
    }

    /**
     * Returns the problematic file path (or URL).
     *
     * @return string The path (or URL).
     */
    public function getProblemFile()
    {
        return $this->problemFile;
    }
}