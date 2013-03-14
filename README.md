Amend
=====

[![Build Status](https://secure.travis-ci.org/kherge/Amend.png?branch=master)](http://travis-ci.org/kherge/Amend)

Integrates [Phar Update](https://github.com/herrera-io/php-phar-update) to [Symfony Console](https://github.com/symfony/Console).

Summary
-------

Uses the Phar Update library to:

1. check for newer versions of the Phar
1. download the Phar
    - verify download by SHA1 checksum, and public key if available
1. replace running Phar with downloaded update

Installation
------------

Add it to your list of Composer dependencies:

```sh
$ composer require kherge/amend=3.*
```

Usage
-----

```php
<?php

use KevinGH\Amend\Command;
use KevinGH\Amend\Helper;
use Symfony\Component\Console\Application;

$command = new Command('update');
$command->setManifestUri('http://box-project.org/manifest.json');

$app = new Application();
$app->getHelperSet()->set(new Helper());
$app->add($command);
```