<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Migrations;

use AngieModelMigration;
use AngieModule;

interface MigrationsInterface
{
    /**
     * Migrate the database up.
     *
     * @param  string $to_version
     * @return array
     */
    public function up($to_version = null, callable $output = null);

    /**
     * Return a list of migration scripts.
     *
     * @param  string                $for_version
     * @return AngieModelMigration[]
     */
    public function getScripts($for_version);

    /**
     * Return a particular script.
     *
     * @param  string              $changeset
     * @param  string              $script
     * @return AngieModelMigration
     */
    public function getScript($changeset, $script);

    public function getLastExecutedScript(): ?AngieModelMigration;
    public function forgetExecutedScript(AngieModelMigration $script): void;

    /**
     * Return scripts form a given module.
     *
     * @return AngieModelMigration[]
     */
    public function getScriptsInModule(AngieModule $module);

    /**
     * Return time stamp from a given change-set name.
     *
     * @param  string      $name
     * @return string|bool
     */
    public function getChangesetTimestamp($name);
}
