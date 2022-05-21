<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRenameUserDevicesUniqueId extends AngieModelMigration
{
    public function up()
    {
        $table = $this->useTableForAlter('user_devices');
        $column = $table->getColumn('unique_id');
        if($column){
            $table->alterColumn('unique_id', DBStringColumn::create('unique_key', 128));
        }
        $this->doneUsingTables();
    }
}
