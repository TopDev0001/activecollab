<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove type column from modification logs, if exists.
 *
 * @package angie.migrations
 */
class MigrateCleanUpModificationType extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $table = $this->loadTable('modification_logs');

        if ($table->getColumn('type') instanceof DBColumn) {
            $table->dropColumn('type');
        }
    }
}
