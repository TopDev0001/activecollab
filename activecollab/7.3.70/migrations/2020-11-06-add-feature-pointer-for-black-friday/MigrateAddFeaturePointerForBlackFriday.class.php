<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\FeaturePointer\BlackFridayFeaturePointer;

class MigrateAddFeaturePointerForBlackFriday extends AngieModelMigration
{
    public function up()
    {
        if (!$this->executeFirstCell('SELECT COUNT(id) FROM `feature_pointers` WHERE `type` = ?', BlackFridayFeaturePointer::class)) {
            $this->execute(
                'INSERT INTO feature_pointers (`type`, `parent_id`, `created_on`) VALUES (?, ?, ?)',
                BlackFridayFeaturePointer::class,
                null,
                new DateTimeValue()
            );
        }
    }
}
