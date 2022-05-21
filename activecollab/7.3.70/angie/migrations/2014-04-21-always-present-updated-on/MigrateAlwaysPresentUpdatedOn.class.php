<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Make sure that updated_on value is always present.
 *
 * @package angie.migrations
 */
class MigrateAlwaysPresentUpdatedOn extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (DB::listTables() as $table) {
            $table_fields = DB::listTableFields($table);

            if (in_array('created_on', $table_fields) && in_array('updated_on', $table_fields)) {
                $this->execute("UPDATE $table SET updated_on = created_on WHERE updated_on IS NULL");
            } elseif (in_array('updated_on', $table_fields)) {
                $this->execute("UPDATE $table SET updated_on = NOW() WHERE updated_on IS NULL");
            }
        }
    }
}
