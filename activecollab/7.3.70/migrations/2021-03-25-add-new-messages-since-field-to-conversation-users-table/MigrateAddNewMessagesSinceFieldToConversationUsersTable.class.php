<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddNewMessagesSinceFieldToConversationUsersTable extends AngieModelMigration
{
    public function up()
    {
        $table_name = 'conversation_users';

        if ($this->tableExists($table_name)) {
            $table = $this->useTableForAlter($table_name);

            if (!$table->getColumn('new_messages_since')) {
                $table->addColumn(
                    new DBDateTimeColumn('new_messages_since')
                );
            }

            $this->doneUsingTables();
        }
    }
}
