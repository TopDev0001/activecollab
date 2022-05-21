<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddNotificationSentAtAtStopwatches extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('stopwatches')) {
            $stopwatches = $this->useTableForAlter('stopwatches');

            if (!$stopwatches->getColumn('notification_sent_at')) {
                $stopwatches->addColumn(new DBDateTimeColumn('notification_sent_at'));
            }

            $this->doneUsingTables();
        }
    }
}
