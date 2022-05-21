<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Conversation\GroupConversation;

class MigrateAddIsAdminColumn extends AngieModelMigration
{
    public function up()
    {
        $conversation_users = $this->useTableForAlter('conversation_users');

        if (!$conversation_users->getColumn('is_admin')) {
            $conversation_users->addColumn(
                new DBBoolColumn('is_admin'),
                'user_id'
            );
        }

        $this->doneUsingTables();

        // set creator of group conversations as admin of it
        $this->execute(
            'UPDATE conversation_users cu
                    LEFT JOIN conversations c ON c.id = cu.conversation_id
                    SET cu.is_admin = 1
                    WHERE c.type = ? AND cu.user_id = c.created_by_id',
            GroupConversation::class
        );
    }
}
