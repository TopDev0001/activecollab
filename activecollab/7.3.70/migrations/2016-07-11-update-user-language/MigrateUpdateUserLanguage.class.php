<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateUserLanguage extends AngieModelMigration
{
    public function up()
    {
        $this->execute('UPDATE ' . $this->useTables('users')[0] . ' SET language_id = ? WHERE language_id = ?', 1, 0);
    }
}
