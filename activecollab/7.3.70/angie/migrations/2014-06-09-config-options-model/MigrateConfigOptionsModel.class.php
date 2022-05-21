<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Config options model.
 *
 * @package angie.migrations
 */
class MigrateConfigOptionsModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $config_options = $this->useTableForAlter('config_options');

        $config_options->addColumn(DBIntegerColumn::create('id', DBColumn::NORMAL, 0), true);
        $config_options->dropColumn('module');

        $config_options->dropPrimaryKey();
        $config_options->addIndex(DBIndex::create('name', DBIndex::UNIQUE));

        $counter = 1;
        foreach ($this->executeFirstColumn('SELECT name FROM ' . $config_options->getName()) as $option_name) {
            $this->execute('UPDATE ' . $config_options->getName() . ' SET id = ? WHERE name = ?', $counter++, $option_name);
        }

        $config_options->alterColumn('id', DBIntegerColumn::create('id', DBIntegerColumn::NORMAL, 0)->setAutoIncrement(true));

        $config_options->addColumn(new DBUpdatedOnColumn(), 'value');
        $this->execute('UPDATE ' . $config_options->getName() . ' SET updated_on = UTC_TIMESTAMP()');

        $this->doneUsingTables();
    }
}
