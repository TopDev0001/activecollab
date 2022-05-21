<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateResetLabelsInSampleProjects extends AngieModelMigration
{
    public function up()
    {
        $this->execute('UPDATE projects SET label_id = 0 WHERE is_sample = 1');
    }
}
