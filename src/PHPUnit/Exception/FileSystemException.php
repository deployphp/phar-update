<?php

namespace Deployer\Component\PHPUnit\Exception;

use Exception;
use RuntimeException;

/**
 * This exception is thrown if there is a problem using the file system.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class FileSystemException extends RuntimeException implements ExceptionInterface
{
    /**
     * The relevant file or directory path.
     *
     * @var string
     */
    private $relevantPath;

    /**
     * Sets the relevant file or directory path, and exception message.
     *
     * @param string $path The relevant path.
     * @param string $message The exception message.
     * @param Exception $previous The previous exception, if any.
     */
    public function __construct(
        $path,
        $message = null,
        Exception $exception = null
    )
    {
        $this->relevantPath = $path;

        parent::__construct($message, null, $exception);
    }

    /**
     * Returns the relevant file or directory path.
     *
     * @return string The relevant path.
     */
    public function getRelevantPath()
    {
        return $this->relevantPath;
    }

    /**
     * Creates an exception for invalid file or directory paths.
     *
     * @param string $path The file or directory path.
     *
     * @return FileSystemException The new exception.
     */
    public static function invalidPath($path)
    {
        return new self($path, sprintf(
            'The path "%s" does not exist.',
            $path
        ));
    }

    /**
     * Uses the last error message to create a new exception.
     *
     * @param string $path The file or directory path.
     * @param string $prefix The string to prefix to the error message.
     *
     * @return FileSystemException The new exception.
     */
    public static function lastError($path, $prefix = '')
    {
        $error = error_get_last();

        return new self($path, $prefix . $error['message']);
    }
}