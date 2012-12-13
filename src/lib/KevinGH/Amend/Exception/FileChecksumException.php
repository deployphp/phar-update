<?php

namespace KevinGH\Amend\Exception;

use UnexpectedValueException;

/**
 * This exception is used when a downloaded file has an invalid checksum.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class FileChecksumException extends UnexpectedValueException
{
    /**
     * The checksum of the new file.
     *
     * @var string
     */
    private $newChecksum;

    /**
     * The path to the new file.
     *
     * @var string
     */
    private $newPath;

    /**
     * The checksum of the source file.
     *
     * @var string
     */
    private $sourceChecksum;

    /**
     * The path (or URL) to the source file.
     *
     * @var string
     */
    private $sourcePath;

    /**
     * Sets the checksum and path of the new and source files.
     *
     * @param string $sourcePath     The source file path (or URL).
     * @parma string $sourceChecksum The source file checksum.
     * @param string $newPath        The new file path.
     * @param string $newChecksum    The new file checksum.
     */
    public function __construct(
        $sourcePath,
        $sourceChecksum,
        $newPath,
        $newChecksum
    ){
        $this->newChecksum = $newChecksum;
        $this->newPath = $newPath;
        $this->sourceChecksum = $sourceChecksum;
        $this->sourcePath = $sourcePath;

        parent::__construct('The file did not match the expected checksum.');
    }

    /**
     * Returns the checksum of the new file.
     *
     * @return string The checksum.
     */
    public function getNewChecksum()
    {
        return $this->newChecksum;
    }

    /**
     * Returns the path to the new file.
     *
     * @return string The path.
     */
    public function getNewPath()
    {
        return $this->newPath;
    }

    /**
     * Returns the checksum of the source file.
     *
     * @return string The checksum.
     */
    public function getSourceChecksum()
    {
        return $this->sourceChecksum;
    }

    /**
     * Returns the path (or URL) to the source file.
     *
     * @return string The path (or URL).
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }
}