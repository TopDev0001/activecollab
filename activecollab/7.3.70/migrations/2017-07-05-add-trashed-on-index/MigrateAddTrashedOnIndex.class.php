<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

final class MigrateAddTrashedOnIndex extends AngieModelMigration
{
    public function up()
    {
        foreach (DB::listTables() as $table_name) {
            if ($this->shouldAddIndex($table_name)) {
                $this
                    ->useTableForAlter($table_name)
                        ->addIndex(new DBIndex('trashed_on'));
            }
        }
    }

    private function shouldAddIndex($table_name)
    {
        return in_array('trashed_on', DB::listTableFields($table_name))
            && !in_array('trashed_on', DB::listTableIndexes($table_name));
    }
}
