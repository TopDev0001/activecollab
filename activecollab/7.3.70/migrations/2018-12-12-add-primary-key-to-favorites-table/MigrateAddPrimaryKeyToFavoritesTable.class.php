<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddPrimaryKeyToFavoritesTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('favorites')) {
            $favorites = $this->useTableForAlter('favorites');

            if (!$favorites->getColumn('id')) {
                DB::execute(
                    'ALTER TABLE favorites ADD COLUMN `id` int(10) unsigned PRIMARY KEY AUTO_INCREMENT FIRST'
                );
            }

            $this->doneUsingTables();
        }
    }
}
