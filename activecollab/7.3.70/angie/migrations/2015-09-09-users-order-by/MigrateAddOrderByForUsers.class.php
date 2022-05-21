<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add order_by column to users table.
 *
 * @package angie.migrations
 */
class MigrateAddOrderByForUsers extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        // Add order_by field, populate it and add index on it
        $users_table = $this->useTableForAlter('users');

        if (!$users_table->getColumn('order_by')) {
            $users_table->addColumn(DBStringColumn::create('order_by', 191));
            $this->execute('UPDATE ' . $users_table->getName() . ' SET order_by = CONCAT(first_name, last_name, email)');
            $users_table->addIndex(DBIndex::create('order_by'));
        }

        // Create triggers for INSERT and UPDATE events for users table
        foreach (['INSERT', 'UPDATE'] as $event) {
            $trigger_name = 'order_by_for_users_before_' . strtolower($event);

            // drop any previously declared triggers with the given name
            $this->execute('DROP TRIGGER IF EXISTS ' . $trigger_name);

            // create triggers
            $this->execute("CREATE TRIGGER $trigger_name BEFORE $event ON users FOR EACH ROW SET NEW.order_by = CONCAT(NEW.first_name, NEW.last_name, NEW.email)");
        }

        $this->doneUsingTables();
    }
}
