<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRemoveUnusedFeaturePointers extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('feature_pointers') && !$this->tableExists('feature_pointer_dismissals')) {
            return;
        }

        $features = [
            'ActiveCollab\Module\System\Model\FeaturePointer\ChatFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\LiveSupportFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\AddOnsFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\TimesheetFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\NewMentionsFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\WorkloadInThePastFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\NewProjectPageFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\MyWorkGroupedTasksFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\InvoicesQRCodeFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\BrowserNotificationsFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\ChristmasDiscountFeaturePointer',
            'ActiveCollab\Module\System\Model\FeaturePointer\NewColumnViewFeaturePointer',
        ];

        $feature_ids = DB::executeFirstColumn('SELECT id FROM feature_pointers WHERE type IN (?)', $features);

        if (!empty($feature_ids)) {
            DB::execute('DELETE from feature_pointer_dismissals WHERE feature_pointer_id IN (?)', $feature_ids);
            FeaturePointers::delete(['id IN (?)', $feature_ids]);
        }

        ConfigOptions::removeOption('show_quickbooks_oauth2_migration_pointer');

    }
}
