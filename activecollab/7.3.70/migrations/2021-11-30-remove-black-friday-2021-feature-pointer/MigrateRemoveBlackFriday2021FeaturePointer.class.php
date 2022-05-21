<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRemoveBlackFriday2021FeaturePointer extends AngieModelMigration
{
    public function up()
    {
        $feature_pointer_id = $this->executeFirstCell(
            'SELECT id FROM feature_pointers WHERE `type` LIKE ? ',
            '%BlackFridayFeaturePointer%'
        );

        if ($feature_pointer_id) {
            $this->execute('DELETE FROM feature_pointer_dismissals WHERE `feature_pointer_id` = ?',
                $feature_pointer_id
            );

            $this->execute(
                'DELETE FROM feature_pointers WHERE `id` = ?',
                $feature_pointer_id
            );
        }
    }
}
