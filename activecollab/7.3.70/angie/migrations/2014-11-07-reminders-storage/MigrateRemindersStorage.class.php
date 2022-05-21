<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate reminders storage.
 *
 * @package angie.migrations
 */
class MigrateRemindersStorage extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('reminder_users');

        $reminders = $this->useTableForAlter('reminders');

        $this->sendUnsetReminders($reminders);
        $this->sendOnDatetimeToDate($reminders);

        $reminders->addColumn(
            new DBTypeColumn('CustomReminder', 50, false),
            'id'
        );

        $reminders->dropColumn('send_to');
        $reminders->dropColumn('sent_on');
        $reminders->dropColumn('selected_user_id');
        $reminders->dropColumn('dismissed_on');

        if ($reminders->getColumn('user_id')) {
            $reminders->dropColumn('user_id');
        }

        foreach (['created_by_id', 'created_on', 'send_on'] as $field) {
            if (!$reminders->indexExists($field)) {
                $reminders->addIndex(DBIndex::create($field));
            }
        }

        $this->doneUsingTables();
    }

    /**
     * Make sure that all unsent reminders are scheduled for sending.
     *
     * @throws InvalidParamError
     */
    private function sendUnsetReminders(DBTable &$reminders)
    {
        $subscriptions = $this->useTables('subscriptions')[0];

        $this->execute('DELETE FROM ' . $reminders->getName() . " WHERE sent_on IS NOT NULL OR parent_type NOT IN ('Task')"); // Already sent or attached to unsupported objects

        if ($rows = $this->execute('SELECT id, parent_type, parent_id, send_to, created_by_id, created_by_name, created_by_email, selected_user_id FROM ' . $reminders->getName())) {
            $now = DateTimeValue::now()->toMySQL();
            $batch = new DBBatchInsert($subscriptions, ['parent_type', 'parent_id', 'user_id', 'user_name', 'user_email', 'subscribed_on', 'code'], 50, DBBatchInsert::REPLACE_RECORDS);

            $remind_task_assignees = $remind_commenters = [];
            $remind_subscribers = $remind_commenters = [];

            foreach ($rows as $row) {
                $parent_type = $row['parent_type'];
                $parent_id = $row['parent_id'];

                switch ($row['send_to']) {
                    case 'self':
                        if ($row['created_by_id']) {
                            $batch->insert('CustomReminder', $row['id'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $now, $this->prepareSubscriptionCode());
                        }

                        break;
                    case 'assignees':
                        if ($parent_type == 'Task' && $parent_id) {
                            if (empty($remind_task_assignees[$parent_id])) {
                                $remind_task_assignees[$parent_id] = [];
                            }

                            $remind_task_assignees[$parent_id][] = $row['id'];
                        }

                        break;
                    case 'subscribers':
                        if ($parent_type && $parent_id) {
                            if (empty($remind_subscribers[$parent_type])) {
                                $remind_subscribers[$parent_type] = [];
                            }

                            if (empty($remind_subscribers[$parent_type][$parent_id])) {
                                $remind_subscribers[$parent_type][$parent_id] = [];
                            }

                            $remind_subscribers[$parent_type][$parent_id][] = $row['id'];
                        }

                        break;
                    case 'commenters':
                        if ($parent_type && $parent_id) {
                            if (empty($remind_commenters[$parent_type])) {
                                $remind_commenters[$parent_type] = [];
                            }

                            if (empty($remind_commenters[$parent_type][$parent_id])) {
                                $remind_commenters[$parent_type][$parent_id] = [];
                            }

                            $remind_commenters[$parent_type][$parent_id][] = $row['id'];
                        }

                        break;
                    case 'selected':
                        if ($row['selected_user_id'] && $user_row = $this->executeFirstRow('SELECT id, first_name, last_name, email FROM users WHERE id = ? AND is_trashed = ?', $row['selected_user_id'], false)) {
                            $batch->insert('CustomReminder', $row['id'], $user_row['id'], $this->prepareDisplayName($user_row['first_name'], $user_row['last_name'], $user_row['email']), $user_row['email'], $now, $this->prepareSubscriptionCode());
                        }
                        break;
                }
            }

            $this->remindSubscribers($batch, $remind_subscribers, $now);
            $this->remindCommenters($batch, $remind_commenters, $now);
            $this->remindTaskAssignees($batch, $remind_task_assignees, $now);

            $batch->done();
        }
    }

    /**
     * Prepare a new subscription code.
     *
     * @return string
     */
    private function prepareSubscriptionCode()
    {
        return make_string(10);
    }

    /**
     * Return display name of user based on given parameters.
     *
     * @param  string $first_name
     * @param  string $last_name
     * @param  string $email
     * @return string
     */
    private function prepareDisplayName($first_name, $last_name, $email)
    {
        if ($first_name && $last_name) {
            return $first_name . ' ' . $last_name;
        } elseif ($first_name) {
            return $first_name;
        } elseif ($last_name) {
            return $last_name;
        } else {
            return substr($email, 0, strpos($email, '@'));
        }
    }

    /**
     * @param string $now
     */
    private function remindSubscribers(DBBatchInsert &$batch, array $remind_subscribers, $now)
    {
        if (count($remind_subscribers)) {
            $type_filter = [];

            foreach ($remind_subscribers as $type => $type_objects) {
                $type_filter[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $type, array_keys($type_objects));
            }

            $type_filter = '(' . implode(' OR ', $type_filter) . ')';

            if ($rows = $this->execute('SELECT parent_type, parent_id, user_id, user_name, user_email FROM ' . $this->useTables('subscriptions')[0] . " WHERE $type_filter AND user_id > ?", 0)) {
                foreach ($rows as $row) {
                    $parent_type = $row['parent_type'];
                    $parent_id = $row['parent_id'];

                    if (isset($remind_subscribers[$parent_type]) && isset($remind_subscribers[$parent_type][$parent_id])) {
                        foreach ($remind_subscribers[$parent_type][$parent_id] as $reminder_id) {
                            $batch->insert('CustomReminder', $reminder_id, $row['user_id'], $row['user_name'], $row['user_email'], $now, $this->prepareSubscriptionCode());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $now
     */
    private function remindCommenters(DBBatchInsert &$batch, array $remind_commenters, $now)
    {
        if (count($remind_commenters)) {
            $type_filter = [];

            foreach ($remind_commenters as $type => $type_objects) {
                $type_filter[] = DB::prepare('(c.parent_type = ? AND c.parent_id IN (?))', $type, array_keys($type_objects));
            }

            $type_filter = '(' . implode(' OR ', $type_filter) . ')';

            [$comments, $users] = $this->useTables('comments', 'users');

            if ($rows = $this->execute("SELECT c.parent_type, c.parent_id, c.created_by_id, u.first_name, u.last_name, u.email FROM $comments AS c LEFT JOIN $users AS u ON c.created_by_id = u.id WHERE c.created_by_id > ? AND $type_filter AND u.is_trashed = ?", 0, false)) {
                foreach ($rows as $row) {
                    $parent_type = $row['parent_type'];
                    $parent_id = $row['parent_id'];

                    if (isset($remind_commenters[$parent_type]) && isset($remind_commenters[$parent_type][$parent_id])) {
                        foreach ($remind_commenters[$parent_type][$parent_id] as $reminder_id) {
                            $batch->insert('CustomReminder', $reminder_id, $row['created_by_id'], $this->prepareDisplayName($row['first_name'], $row['last_name'], $row['email']), $row['email'], $now, $this->prepareSubscriptionCode());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  string            $now
     * @throws InvalidParamError
     */
    private function remindTaskAssignees(DBBatchInsert &$batch, array $remind_task_assignees, $now)
    {
        if (count($remind_task_assignees)) {
            [$tasks, $users] = $this->useTables('tasks', 'users');

            if ($rows = $this->execute("SELECT t.id, t.assignee_id, u.first_name, u.last_name, u.email FROM $tasks AS t LEFT JOIN $users AS u ON t.assignee_id = u.id WHERE t.id IN (?) AND t.assignee_id > ? AND u.is_trashed = ?", array_keys($remind_task_assignees), 0, false)) {
                foreach ($rows as $row) {
                    foreach ($remind_task_assignees[$row['id']] as $reminder_id) {
                        $batch->insert('CustomReminder', $reminder_id, $row['assignee_id'], $this->prepareDisplayName($row['first_name'], $row['last_name'], $row['email']), $row['email'], $now, $this->prepareSubscriptionCode());
                    }
                }
            }
        }
    }

    private function sendOnDatetimeToDate(DBTable &$reminders)
    {
        $reminders->addColumn(
            new DBDateColumn('send_on_new'),
            'send_on'
        );

        $this->execute('UPDATE ' . $reminders->getName() . ' SET send_on_new = DATE(send_on)');

        $reminders->dropColumn('send_on');
        $reminders->alterColumn(
            'send_on_new',
            new DBDateColumn('send_on')
        );
    }
}
