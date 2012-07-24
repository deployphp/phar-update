<?php

    /* This file is part of Amend.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace Mock;

    use KevinGH\Amend\Command as _Command;

    class Command extends _Command
    {
        protected $extract = '/^test\-(.+?)\.phar$/';
        protected $match = '/^test\-(.+?)\.phar$/';
    }