<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateMaxLimitElapsedFieldStopwatches extends AngieModelMigration
{
    public function up()
    {
        DB::execute('UPDATE stopwatches SET elapsed = 359999 WHERE started_on IS NULL AND elapsed > ?', 359999);
    }
}
