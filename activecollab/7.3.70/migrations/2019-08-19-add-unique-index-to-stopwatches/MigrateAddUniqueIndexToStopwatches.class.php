<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddUniqueIndexToStopwatches extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('stopwatches')) {
            $this->execute('
                            DELETE s1 FROM stopwatches s1 INNER JOIN stopwatches s2 
                            WHERE s1.id < s2.id 
                            AND s1.parent_type = s2.parent_type AND s1.parent_id = s2.parent_id AND s1.user_id = s2.user_id;
            ');

            $stopwatches = $this->useTableForAlter('stopwatches');

            $stopwatches->addIndex(DBIndex::create(
                'parent_key_reference',
                DBIndex::UNIQUE, [
                    'parent_type',
                    'parent_id',
                    'user_id',
                ]
            ));

            $this->doneUsingTables();
        }
    }
}
