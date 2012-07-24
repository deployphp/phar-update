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

    use PHPUnit_Framework_TestCase,
        ReflectionMethod,
        ReflectionProperty;

    class TestCase extends PHPUnit_Framework_TestCase
    {
        /**
         * The project temporary prefix.
         *
         * @type string
         */
        const PREFIX = 'ame';

        /**
         * The list of created directories and files.
         *
         * @type array
         */
        private $temporary = array();

        /**
         * Removes the created temporary paths.
         */
        protected function tearDown()
        {
            foreach ($this->temporary as $path)
            {
                $this->remove($path);
            }
        }

        /**
         * Creates a temporary directory.
         *
         * @return string The temporary directory path.
         */
        public function dir()
        {
            unlink($path = $this->file());

            mkdir($path);

            $this->temporary[] = $path;

            return $path;
        }

        /**
         * Creates a temporary file.
         *
         * @param mixed $content The file's new content.
         * @return string The temporary file path.
         */
        public function file($content = null)
        {
            if (false === ($file = tempnam(sys_get_temp_dir(), self::PREFIX)))
            {
                throw new RuntimeException('Could not create temporary file.');
            }

            if (null !== $content)
            {
                file_put_contents($file, $content);
            }

            $this->temporary[] = $file;

            return $file;
        }

        /**
         * Returns the method as ReflectionMethod.
         *
         * @param object $object The object.
         * @param string $name The method nam.
         * @return Closure The method.
         */
        public function method($object, $name)
        {
            $method = new ReflectionMethod($object, $name);

            $method->setAccessible(true);

            return function () use ($object, $method)
            {
                return $method->invokeArgs($object, func_get_args());
            };
        }

        /**
         * Returns the property as ReflectionProperty.
         *
         * @param object $object The object.
         * @param string $name The property name.
         * @return Closure The property.
         */
        public function property($object, $name)
        {
            $property = new ReflectionProperty($object, $name);

            $property->setAccessible(true);

            return function () use ($object, $property)
            {
                if (0 < func_num_args())
                {
                    $property->setValue($object, func_get_arg(0));
                }

                else
                {
                    return $property->getValue($object);
                }
            };
        }

        /**
         * Redeclares an existing function while preserving the original.
         *
         * @param string $name The function name.
         * @param string $args The arguments.
         * @param string $body The function body.
         * @return boolean TRUE if test skipped, FALSE if not.
         */
        public function redeclare($name, $args, $body)
        {
            if (extension_loaded('runkit'))
            {
                runkit_function_rename($name, "_$name");

                runkit_function_add($name, $args, $body);

                return false;
            }

            $this->markTestSkipped('The "runkit" extension is not available.');

            return true;
        }

        /**
         * Recursively removes the path.
         *
         * @param string $path The path to remove.
         */
        public function remove($path)
        {
            if ($path = realpath($path))
            {
                if (is_dir($path) && (false === is_link($path)))
                {
                    foreach (scandir($path) as $node)
                    {
                        if (in_array($node, array('.', '..')))
                        {
                            continue;
                        }

                        $nodePath = $path . DIRECTORY_SEPARATOR . $node;

                        $this->remove($nodePath);
                    }

                    rmdir($path);
                }

                else
                {
                    unlink($path);
                }
            }
        }

        /**
         * Restores the original function that was redeclared.
         *
         * @param string $name The function name.
         */
        public function restore($name)
        {
            runkit_function_remove($name);

            runkit_function_rename("_$name", $name);
        }
    }