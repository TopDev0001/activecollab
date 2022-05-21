<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Bootstrap CLI environment.
 *
 * @package angie
 */
if (php_sapi_name() != 'cli') {
    die("Error: CLI only\n");
}

if (isset($this) && $this instanceof \SebastianBergmann\CodeCoverage\CodeCoverage) {
    return;
}

set_time_limit(0);

// Bootstrap for command line, with router, events and modules
if (AngieApplication::isInstalled()) {
    AngieApplication::bootstrapForCommandLineRequest();
} else {
    AngieApplication::bootstrapForInstallation();
}

// Load resources and create a new console application
use Symfony\Component\Console\Application;

$application = new Application(
    AngieApplication::getName(),
    (AngieApplication::getVersion() === 'current' ? '5.0.0' : AngieApplication::getVersion())
);

// Load Angie classes
foreach (new DirectoryIterator(ANGIE_PATH . '/src/Angie/Command') as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $class_name = ('\\Angie\\Command\\' . $file->getBasename('.php'));

        $class = new ReflectionClass($class_name);

        if (!$class->isTrait() && !$class->isAbstract()) {
            $application->add(new $class_name());
        }
    }
}

$application_commands_path = APPLICATION_PATH . '/Command';
$application_commands_path_len = strlen(APPLICATION_PATH . '/Command');

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($application_commands_path), RecursiveIteratorIterator::SELF_FIRST) as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        require_once $file->getPathname();

        $class_name = ('\\' . AngieApplication::getName() . '\\Command\\' . implode('\\', explode('/', substr($file->getPath() . '/' . $file->getBasename('.php'), $application_commands_path_len + 1))));

        if (!(new ReflectionClass($class_name))->isAbstract()) {
            $application->add(new $class_name());
        }
    }
}

$application->run();
