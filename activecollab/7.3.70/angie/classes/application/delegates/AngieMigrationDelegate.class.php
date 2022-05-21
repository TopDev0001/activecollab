<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Migrations\Migrations;
use Angie\Migrations\MigrationsInterface;

if (!class_exists(MigrationsInterface::class, false)) {
    require_once dirname(dirname(dirname(__DIR__))) . '/src/Angie/Migrations/MigrationsInterface.php';
}

if (!class_exists(Migrations::class, false)) {
    require_once dirname(dirname(dirname(__DIR__))) . '/src/Angie/Migrations/Migrations.php';
}

/**
 * This class exists for compatibility reasons.
 *
 * Older versions of ActiveCollab expect this file, and this class, on this location. We have moved basic behavior in
 * the base, namespaced and refactored class.
 *
 * @deprecated
 */
final class AngieMigrationDelegate extends AngieDelegate
{
    private $migrations;

    public function __construct()
    {
        $this->migrations = new Migrations();
    }

    public function up($to_version = null, callable $output = null)
    {
        return $this->migrations->up($to_version, $output);
    }

    public function getScripts($for_version)
    {
        return $this->migrations->getScripts($for_version);
    }
}
