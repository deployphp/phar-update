Phar Update
=====

[![Build Status](https://travis-ci.org/deployphp/phar-update.svg?branch=master)](https://travis-ci.org/deployphp/phar-update)

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
$ composer require deployer/phar-update
```

Usage
-----

```php
use Deployer\Component\PharUpdate\Console\Command;
use Deployer\Component\PharUpdate\Console\Helper;
use Symfony\Component\Console\Application;

$command = new Command('update');
$command->setManifestUri('https://deployer.org/manifest.json');

$app = new Application();
$app->getHelperSet()->set(new Helper());
$app->add($command);
```