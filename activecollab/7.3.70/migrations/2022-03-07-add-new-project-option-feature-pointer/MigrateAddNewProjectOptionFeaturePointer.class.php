<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\FeaturePointer\NewProjectOptionsFeaturePointer;

class MigrateAddNewProjectOptionFeaturePointer extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('feature_pointers')) {
            return;
        }

        $this->execute(
            'INSERT INTO `feature_pointers` (`type`, `created_on`, `expires_on`) VALUES (?, ?, ?)',
            NewProjectOptionsFeaturePointer::class,
            new DateTimeValue(),
            (new DateValue())->addDays(15),
        );
    }
}
