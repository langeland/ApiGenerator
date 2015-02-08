#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/Libraries/autoload.php';

$application = new \Symfony\Component\Console\Application('ApiGenerator', '0.1-dev');
$application->add(new ApiGenerator\Command\EnvironmentSetupCommand());
$application->add(new ApiGenerator\Command\BuildCommand());
$application->run();
