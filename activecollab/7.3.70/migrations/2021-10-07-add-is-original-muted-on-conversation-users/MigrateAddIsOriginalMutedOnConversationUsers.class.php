<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddIsOriginalMutedOnConversationUsers extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('conversation_users')) {
            $conversation_users = $this->useTableForAlter('conversation_users');

            if (!$conversation_users->getColumn('is_original_muted')) {
                $conversation_users->addColumn(
                    (new DBBoolColumn('is_original_muted')),
                    'is_muted'
                );
            }

            $this->doneUsingTables();
        }
    }
}
