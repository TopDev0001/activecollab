<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove subscribed clients from hidden tasks.
 *
 * @package activeCollab.modules.system
 */
class MigrateRemoveSubscribedClientsFromHiddenTasks extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$subscriptions, $tasks, $users] = $this->useTables('subscriptions', 'tasks', 'users');

        if ($subscription_ids = $this->executeFirstColumn("SELECT s.id FROM $subscriptions as s LEFT JOIN $tasks as t ON t.id = s.parent_id LEFT JOIN $users as u ON u.id = s.user_id WHERE s.parent_type = ? AND t.is_hidden_from_clients = ? AND u.type = ?", 'Task', true, 'Client')) {
            $this->execute("DELETE FROM $subscriptions WHERE id IN (?)", $subscription_ids);
        }

        $this->doneUsingTables();
    }
}
