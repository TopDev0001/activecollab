<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTwoNewFieldsToBudgetThresholds extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('budget_thresholds')) {
            $budget_thresholds = $this->useTableForAlter('budget_thresholds');

            if (!$budget_thresholds->getColumn('is_notification_sent')) {
                $budget_thresholds->addColumn(
                    new DBBoolColumn('is_notification_sent'),
                    'created_by_email'
                );
            }

            if (!$budget_thresholds->getColumn('notification_sent_on')) {
                $budget_thresholds->addColumn(
                    new DBDateTimeColumn('notification_sent_on'),
                    'is_notification_sent'
                );
            }

            if ($budget_thresholds->getColumn('notification_sent_on') && $budget_thresholds->getColumn('is_notification_sent')) {
                // now populate two new fields with data from budget_thresholds_notifications table
                $budget_thresholds_notifications = $this->loadTable('budget_thresholds_notifications');
                // this check is added bcs of broken migration in some sh users
                if ($budget_thresholds_notifications->getColumn('parent_id')) {
                    $this->execute('
                    UPDATE budget_thresholds AS bt
                    INNER JOIN
                        (SELECT parent_id, MAX(sent_at) AS sent_at
                        FROM budget_thresholds_notifications
                        GROUP BY parent_id) AS btn ON bt.id = btn.parent_id
                    SET bt.is_notification_sent = 1, bt.notification_sent_on = btn.sent_at');
                } else {
                    $this->execute('
                    UPDATE budget_thresholds AS bt
                    INNER JOIN
                        (SELECT project_id, type, threshold, MAX(sent_at) AS sent_at
                        FROM budget_thresholds_notifications
                        GROUP BY project_id, type, threshold) AS btn ON bt.project_id = btn.project_id AND bt.type = btn.type AND bt.threshold = btn.threshold
                    SET bt.is_notification_sent = 1, bt.notification_sent_on = btn.sent_at');
                }

                // update the not notified thresholds if there is a higher threshold already notified
                /** @var DBResult $result */
                $result = $this->execute('SELECT id, project_id, threshold, type FROM budget_thresholds WHERE is_notification_sent = 0');
                if ($result) {
                    $not_notified_thresholds_ids = $result->toArray();

                    $query = '
                        SELECT notification_sent_on
                        FROM budget_thresholds
                        WHERE project_id = ? AND threshold > ? AND type = ? AND is_notification_sent = 1
                        ORDER BY threshold
                        LIMIT 1';

                    $update_query = 'UPDATE budget_thresholds SET is_notification_sent = 1, notification_sent_on = ? WHERE id = ?';

                    if ($not_notified_thresholds_ids) {
                        foreach ($not_notified_thresholds_ids as $threshold) {
                            $sent_on = $this->executeFirstCell($query, $threshold['project_id'], $threshold['threshold'], $threshold['type']);
                            if ($sent_on) {
                                $this->execute($update_query, $sent_on, $threshold['id']);
                            }
                        }
                    }
                }
            }
        }
    }
}
