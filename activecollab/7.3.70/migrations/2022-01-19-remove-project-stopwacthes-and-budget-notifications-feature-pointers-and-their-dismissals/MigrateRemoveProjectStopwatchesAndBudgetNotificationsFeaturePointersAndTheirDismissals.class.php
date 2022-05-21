<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRemoveProjectStopwatchesAndBudgetNotificationsFeaturePointersAndTheirDismissals extends AngieModelMigration
{
    public function up()
    {
        $stopwatch = 'ActiveCollab\Module\System\Model\FeaturePointer\ProjectStopwatchFeaturePointer';
        $budget = 'ActiveCollab\Module\System\Model\FeaturePointer\BudgetNotificationsFeaturePointer';

        $budget_id = $this->executeFirstCell('SELECT id FROM feature_pointers WHERE type = ?', $budget);
        if ($budget_id) {
            $this->execute('DELETE FROM feature_pointer_dismissals WHERE feature_pointer_id = ?', $budget_id);
        }

        $stopwatches_id = $this->executeFirstCell('SELECT id FROM feature_pointers WHERE type = ?', $stopwatch);
        if ($stopwatches_id) {
            $this->execute('DELETE FROM feature_pointer_dismissals WHERE feature_pointer_id = ?', $stopwatches_id);
        }

        $this->execute('DELETE FROM feature_pointers WHERE type IN (?)', [$stopwatch, $budget]);
    }
}
