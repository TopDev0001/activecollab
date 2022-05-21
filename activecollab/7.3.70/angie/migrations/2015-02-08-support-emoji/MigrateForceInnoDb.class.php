<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Convert MyISAM tables to InnoDB.
 *
 * @package ActiveCollab.migrations
 */
class MigrateForceInnoDb extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (DB::listTables() as $table_name) {
            if ($this->executeFirstRow('SHOW TABLE STATUS WHERE Name = ?', $table_name)['Engine'] != 'InnoDB') {
                $this->execute("ALTER TABLE `$table_name` ENGINE=InnoDB");
            }
        }
    }
}
