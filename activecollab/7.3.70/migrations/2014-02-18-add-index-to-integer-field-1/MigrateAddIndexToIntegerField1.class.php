<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add index on integer_field_1 because it's often used to find tasks and slows down with bigger data sets.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateAddIndexToIntegerField1 extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $indexes = DB::listTableIndexes('project_objects');
        if (!in_array('integer_field_1', $indexes)) {
            DB::execute('ALTER TABLE project_objects ADD INDEX integer_field_1 (integer_field_1)');
        }
    }
}
