<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveSubscribedClientsFromHiddenDiscussionsAndNotes extends AngieModelMigration
{
    public function up()
    {
        [$subscriptions, $discussions, $notes, $users] = $this->useTables('subscriptions', 'discussions', 'notes', 'users');

        foreach (['Discussion' => $discussions, 'Note' => $notes] as $class => $table) {
            if ($subscription_ids = $this->executeFirstColumn("SELECT s.id FROM $subscriptions as s LEFT JOIN $table as t ON t.id = s.parent_id LEFT JOIN $users as u ON u.id = s.user_id WHERE s.parent_type = ? AND t.is_hidden_from_clients = ? AND u.type = ?", $class, true, 'Client')) {
                $this->execute("DELETE FROM $subscriptions WHERE id IN (?)", $subscription_ids);
            }
        }

        $this->doneUsingTables();
    }
}
