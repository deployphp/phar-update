<?php

namespace Deployer\Component\PHPUnit\Exception;

use ReflectionException as Base;

/**
 * This exception is thrown if there is a reflection error.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ReflectionException extends Base implements ExceptionInterface
{
}
