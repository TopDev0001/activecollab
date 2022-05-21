<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\FeaturePointer\BudgetNotificationsFeaturePointer;

class MigrateRemoveBudgetNotificationFeaturePointer extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('feature_pointers')) {
            $feature_pointers_id = DB::executeFirstRow('SELECT * FROM feature_pointers WHERE `type` = ?', BudgetNotificationsFeaturePointer::class);

            if (!empty($feature_pointers_id) && $this->tableExists('feature_pointer_dismissals')) {
                DB::execute('DELETE FROM `feature_pointer_dismissals` WHERE `feature_pointer_id` = ?', $feature_pointers_id['id']);
            }

            DB::execute('DELETE FROM feature_pointers WHERE `type` = ?', BudgetNotificationsFeaturePointer::class);
        }
    }
}
