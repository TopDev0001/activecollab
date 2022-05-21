<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveSummarizedReport extends AngieModelMigration
{
    public function up()
    {
        $this->execute('DELETE FROM data_filters WHERE type = ?', 'SummarizedTrackingFilter');
    }
}
