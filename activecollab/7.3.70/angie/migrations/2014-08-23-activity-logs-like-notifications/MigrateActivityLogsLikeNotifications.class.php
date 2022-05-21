<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateActivityLogsLikeNotifications extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('activity_logs')) {
            $this->dropTable('activity_logs');
        }

        $this->createTable(
            DB::createTable('activity_logs')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn('ActivityLog'),
                    new DBParentColumn(),
                    DBStringColumn::create('parent_path', 255, ''),
                    new DBCreatedOnByColumn(true, true),
                    new DBAdditionalPropertiesColumn(),
                ]
            )->addIndices(
                [
                    DBIndex::create('parent_path', DBIndex::KEY, ['parent_path', 'parent_id']),
                ]
            )
        );
    }
}
