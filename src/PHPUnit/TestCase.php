<?php

namespace Deployer\Component\PHPUnit;

use Deployer\Component\PHPUnit\Exception\FileSystemException;
use Deployer\Component\PHPUnit\Exception\ReflectionException;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Process\ProcessBuilder;

/**
 * A PHPUnit test case class with additional functionality.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * The list of paths to purge.
     *
     * @var array
     */
    private $purgePaths = array();

    /**
     * Calls a class or object method.
     *
     * @param object|string $class  The class name or object.
     * @param string        $method The method name.
     * @param array         $args   The method arguments.
     *
     * @return mixed The method result.
     */
    public function callMethod($class, $method, array $args = array())
    {
        $method = $this->findMethod($class, $method);
        $method->setAccessible(true);

        return $method->invokeArgs(
            is_object($class) ? $class : null,
            $args
        );
    }

    /**
     * Recursively copies a file or directory tree to another location.
     *
     * @param string  $from    The source file system path.
     * @param string  $to      The target file system path.
     * @param boolean $replace Replacing existing files?
     * @param boolean $purge   Automatically purge?
     *
     * @throws FileSystemException If the path could not be copied.
     */
    public function copyPath($from, $to, $replace = true, $purge = true)
    {
        if (false === file_exists($from)) {
            throw FileSystemException::invalidPath($from);
        }

        if (is_dir($from)) {
            if (false === file_exists($to)) {
                if (false === @ mkdir($to)) {
                    throw FileSystemException::lastError(
                        $to,
                        "The directory \"$to\" could not be created: "
                    );
                }
            }

            if (false === ($dh = @ opendir($from))) {
                throw FileSystemException::lastError(
                    $to,
                    "The directory \"$from\" could not be opened: "
                );
            }

            while (false !== ($item = readdir($dh))) {
                if (('.' === $item) || ('..' === $item)) {
                    continue;
                }

                $this->copyPath(
                    $from . DIRECTORY_SEPARATOR . $item,
                    $to . DIRECTORY_SEPARATOR . $item,
                    $replace,
                    false
                );
            }

            closedir($dh);
        } elseif (false === (file_exists($to) && (false === $replace))) {
            if (false === @ copy($from, $to)) {
                throw FileSystemException::lastError($to);
            }
        }

        if ($purge) {
            $this->purgePaths[] = $to;
        }
    }

    /**
     * Creates a temporary directory path that will be automatically purged
     * at the end of the test.
     *
     * @param string $name The directory name.
     *
     * @return string The directory path.
     *
     * @throws FileSystemException If the directory could not be created.
     */
    public function createDir($name = null)
    {
        unlink($dir = $this->createFile());

        if (false === mkdir($dir)) {
            throw FileSystemException::lastError($dir);
        }

        if (null !== $name) {
            $dir .= DIRECTORY_SEPARATOR . $name;

            if (false === mkdir($dir)) {
                throw FileSystemException::lastError($dir);
            }
        }

        return $dir;
    }

    /**
     * Creates a temporary file path that will be automatically purged at the
     * end of the test.
     *
     * @param string $name The file name.
     *
     * @return string The file path.
     *
     * @throws FileSystemException If the file could not be created.
     */
    public function createFile($name = null)
    {
        if (null === $name) {
            if (false === ($file = tempnam(sys_get_temp_dir(), 'tst'))) {
                throw FileSystemException::lastError($name);
            }

            $this->purgePaths[] = $file;
        } else {
            if (false === touch(
                $file = $this->createDir() . DIRECTORY_SEPARATOR . $name
            )){
                throw FileSystemException::lastError($file);
            }
        }

        return $file;
    }

    /**
     * Finds a class method and returns the ReflectionMethod instance.
     *
     * @param object|string $class The class name or object.
     * @param string        $name  The method name.
     *
     * @return ReflectionMethod The method name.
     *
     * @throws ReflectionException If the method does not exist.
     */
    public function findMethod($class, $name)
    {
        $reflection = new ReflectionClass($class);

        while (false === $reflection->hasMethod($name)) {
            if (false === ($reflection = $reflection->getParentClass())) {
                throw new ReflectionException(sprintf(
                    'The method "%s" does not exist in the class "%s".',
                    $name,
                    is_object($class) ? get_class($class) : $class
                ));
            }
        }

        return $reflection->getMethod($name);
    }

    /**
     * Finds a class property and returns the ReflectionProperty instance.
     *
     * @param object|string $class The class name or object.
     * @param string        $name  The property name.
     *
     * @return ReflectionProperty The property instance.
     *
     * @throws ReflectionException If the property is not found.
     */
    public function findProperty($class, $name)
    {
        $reflection = new ReflectionClass($class);

        while (false === $reflection->hasProperty($name)) {
            if (false === ($reflection = $reflection->getParentClass())) {
                throw new ReflectionException(sprintf(
                    'The property "%s" does not exist in the class "%s".',
                    $name,
                    is_object($class) ? get_class($class) : $class
                ));
            }
        }

        return $reflection->getProperty($name);
    }

    /**
     * Returns the value of the property.
     *
     * @param object|string $class The class name or object.
     * @param string        $name  The property name.
     *
     * @return mixed The value of the property.
     */
    public function getPropertyValue($class, $name)
    {
        $property = $this->findProperty($class, $name);
        $property->setAccessible(true);

        return $property->getValue(is_object($class) ? $class : null);
    }

    /**
     * Recursively deletes a file or directory tree.
     *
     * @param string $path The file or directory path.
     *
     * @throws FileSystemException If the path could not be purged.
s    */
    public function purgePath($path)
    {
        if (false === file_exists($path)) {
            throw FileSystemException::invalidPath($path);
        }

        if (is_dir($path)) {
            if (false === ($dh = @ opendir($path))) {
                throw FileSystemException::lastError($path);
            }

            while (false !== ($item = readdir($dh))) {
                if (('.' === $item) || ('..' === $item)) {
                    continue;
                }

                $this->purgePath($path . DIRECTORY_SEPARATOR . $item);
            }

            closedir($dh);

            if (false === @ rmdir($path)) {
                throw FileSystemException::lastError($path);
            }
        } else {
            if (false === @ unlink($path)) {
                throw FileSystemException::lastError($path);
            }
        }
    }

    /**
     * Creates a new Process and returns its ran instance.
     *
     * @param string $command The command.
     * @param mixed  $arg,... An argument.
     *
     * @return Process The ran process.
     */
    public function runProcess()
    {
        $process = ProcessBuilder::create(func_get_args())->getProcess();
        $process->run();

        return $process;
    }

    /**
     * Sets the value of the property.
     *
     * @param object|string $class The class name or object.
     * @param string        $name  The property name.
     * @param mixed         $value The property value.
     */
    public function setPropertyValue($class, $name, $value)
    {
        $property = $this->findProperty($class, $name);
        $property->setAccessible(true);
        $property->setValue(
            is_object($class) ? $class : null,
            $value
        );
    }

    /**
     * Purges the created paths and reverts changes made by Runkit.
     */
    protected function tearDown()
    {
        foreach ($this->purgePaths as $path) {
            if (file_exists($path)) {
                $this->purgePath($path);
            }
        }
    }
}