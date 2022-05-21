<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Make sure that task position can't be null.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskPositionNotNull extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        $this->execute('UPDATE ' . $tasks->getName() . ' SET position = ? WHERE position IS NULL OR position < ?', 0, 0);
        $tasks->alterColumn('position', DBIntegerColumn::create('position', 10, 0)->setUnsigned(true));

        $this->doneUsingTables();
    }
}
