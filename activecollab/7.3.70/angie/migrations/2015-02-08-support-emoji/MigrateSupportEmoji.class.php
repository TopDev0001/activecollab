<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Convert UTF8 tables and columns to UTF8MB4.
 *
 * @package angie.migrations
 */
class MigrateSupportEmoji extends AngieModelMigration
{
    /**
     * Make sure that this migration is executed after we have all tables as InnoDB.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateForceInnoDb');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (DB::listTables() as $table_name) {
            $table = $this->useTableForAlter($table_name);

            foreach ($table->getColumns() as $column) {
                if ($column instanceof DBStringColumn) {
                    if ($column->getLength() > 191) {
                        $this->execute("UPDATE {$table->getName()} SET {$column->getName()} = SUBSTR({$column->getName()}, 0, 191) WHERE CHAR_LENGTH({$column->getName()}) > 191");

                        $table->alterColumn($column->getName(), $column->setLength(191));
                    }
                }
            }

            $this->execute("ALTER TABLE $table_name CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $this->doneUsingTables();
    }
}
