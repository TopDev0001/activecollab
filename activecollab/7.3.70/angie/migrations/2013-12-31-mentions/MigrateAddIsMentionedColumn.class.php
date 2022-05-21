<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsMentionedColumn extends AngieModelMigration
{
    public function up()
    {
        $this->loadTable('notification_recipients')->addColumn(
            new DBBoolColumn('is_mentioned')
        );
    }
}
