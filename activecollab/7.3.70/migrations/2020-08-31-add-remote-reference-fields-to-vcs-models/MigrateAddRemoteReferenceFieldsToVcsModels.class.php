<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddRemoteReferenceFieldsToVcsModels extends AngieModelMigration
{
    public function up()
    {
        $tables_to_update = [
            'vcs_connections',
            'vcs_repositories',
        ];

        foreach ($tables_to_update as $table_to_update) {
            if ($this->tableExists($table_to_update)) {
                $table = $this->useTableForAlter($table_to_update);

                if (!$table->getColumn('remote_reference')) {
                    $table->addColumn(
                        new DBStringColumn('remote_reference', 191, ''),
                        'name'
                    );
                }

                if (!$table->getIndex('remote_reference')) {
                    $table->addIndex(new DBIndex('remote_reference'));
                }
            }
        }

        $connections = $this->execute('SELECT `id`, `raw_additional_properties` FROM `vcs_connections`');

        if ($connections) {
            foreach ($connections as $connection) {
                $properties = [];

                if (!empty($connection['raw_additional_properties'])) {
                    $properties = unserialize($connection['raw_additional_properties']);
                }

                if (!empty($properties['org_login'])) {
                    $org_login = $properties['org_login'];
                    unset($properties['org_login']);

                    $this->execute(
                        'UPDATE `vcs_connections` SET `remote_reference` = ?, `raw_additional_properties` = ? WHERE `id` = ?',
                        $org_login,
                        serialize($properties),
                        $connection['id'],
                    );
                }
            }
        }

        $this->doneUsingTables();
    }
}
