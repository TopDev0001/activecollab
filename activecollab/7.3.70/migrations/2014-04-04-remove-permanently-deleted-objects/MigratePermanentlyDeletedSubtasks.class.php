<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate permannetly deleted subtasks.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigratePermanentlyDeletedSubtasks extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$subtasks, $subscriptions, $favorites] = $this->useTables('subtasks', 'subscriptions', 'favorites');

        defined('STATE_DELETED') or define('STATE_DELETED', 0);

        if ($rows = $this->execute("SELECT id, type FROM $subtasks WHERE state = ?", STATE_DELETED)) {
            $subtask_ids = [];
            $subtask_conditions = [];

            foreach ($rows as $row) {
                $subtask_ids[] = $row['id'];

                if (empty($conditions[$row['type']])) {
                    $subtask_conditions[$row['type']] = [];
                }

                $subtask_conditions[$row['type']][] = $row['id'];
            }

            foreach ($subtask_conditions as $k => $v) {
                $subtask_conditions[$k] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $k, $v);
            }

            $subtask_conditions = '(' . implode(' OR ', $subtask_conditions) . ')';

            $this->execute("DELETE FROM $subtasks WHERE state = ?", STATE_DELETED);

            $this->execute("DELETE FROM $subscriptions WHERE $subtask_conditions");
            $this->execute("DELETE FROM $favorites WHERE $subtask_conditions");

            $this->execute("DELETE FROM $subtasks WHERE id IN (?)", $subtask_ids);
        }
    }
}
