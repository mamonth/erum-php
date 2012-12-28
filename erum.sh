#!/usr/local/bin/php
<?php

include 'bootstrap.php';

Erum::getInstance();

\Erum\CLI::current()
    ->registerOption( array( 'c', 'create' ), 0, CLI::PARAM_STR | CLI::PARAM_REQUIRED, 'Create a new empty project.' )
    ->fetchOptions();

$project = new \Erum\ProjectBuilder(  CLI::getOption( 'create' ) );

// blah blah blah
