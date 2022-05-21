<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateFilesTable extends AngieModelMigration
{
    public function up()
    {
        $this->execute('UPDATE ' . $this->useTables('files')[0] . ' SET type = ? WHERE type = ?', 'LocalFile', 'File');
    }
}
