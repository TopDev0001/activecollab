<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddUniqueIndexForTypeInIntegrationsTable extends AngieModelMigration
{
    public function up()
    {
        $integrations = $this->useTableForAlter('integrations');

        // remove a non-unique rows
        $has_duplicates = DB::execute('SELECT type, COUNT(type) FROM integrations GROUP BY type HAVING COUNT(type) > 1');
        if ($has_duplicates) {
            DB::execute('DELETE i1 FROM integrations AS i1 INNER JOIN integrations AS i2 WHERE i1.id < i2.id AND i1.type = i2.type');
        }

        // alter the type index
        if ($integrations->indexExists('type')) {
            $integrations->alterIndex('type', DBIndex::create('type', DBIndex::UNIQUE));
        } else {
            $integrations->addIndex(DBIndex::create('type', DBIndex::UNIQUE));
        }

        $this->doneUsingTables();
    }
}
