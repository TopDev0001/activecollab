<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Model\FeaturePointer\ProjectStopwatchFeaturePointer;

class MigrateAddProjectStopwatchFeaturePointer extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $this->execute(
                'INSERT INTO feature_pointers (type, parent_id, created_on) VALUES (?, ?, ?)',
                ProjectStopwatchFeaturePointer::class,
                null,
                new DateTimeValue()
            );
        }
    }
}
