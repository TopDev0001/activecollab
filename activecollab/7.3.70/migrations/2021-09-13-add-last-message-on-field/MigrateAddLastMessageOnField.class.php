<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddLastMessageOnField extends AngieModelMigration
{
    public function up()
    {
        $conversations = $this->useTableForAlter('conversations');

        if (!$conversations->getColumn('last_message_on')) {
            $conversations->addColumn(
                (new DBDateTimeColumn('last_message_on'))->setDefault(null),
                'parent_id'
            );
        }

        $this->doneUsingTables();

        $last_messages_by_conversations = $this->execute(
            'SELECT MAX(created_on) AS last_created, conversation_id FROM messages GROUP BY conversation_id'
        );

        $conversation_ids = [];
        $when_then_cases = '';
        foreach ($last_messages_by_conversations ? $last_messages_by_conversations->toArray() : [] as $last_message) {
            $when_then_cases .= "WHEN {$last_message['conversation_id']} THEN '{$last_message['last_created']}' ";
            $conversation_ids[] = $last_message['conversation_id'];
        }

        if (!empty($conversation_ids)) {
            $this->execute(
                "UPDATE conversations SET last_message_on = (CASE `id` {$when_then_cases} END) WHERE id IN (?)",
                $conversation_ids
            );
        }
    }
}
