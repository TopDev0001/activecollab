<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddNewEventForSlackIntegration extends AngieModelMigration
{
    public function up()
    {
        $slack_webhook = DB::executeFirstRow('SELECT * FROM webhooks WHERE type = ?', SlackWebhook::class);

        if ($slack_webhook && !empty($slack_webhook['filter_event_types'])) {
            $filter_event_types = explode(',', $slack_webhook['filter_event_types']);

            if (!in_array('TaskListChangedFromReorder', $filter_event_types)) {
                array_push($filter_event_types, 'TaskListChangedFromReorder');

                DB::execute(
                    'UPDATE webhooks SET filter_event_types = ? WHERE id = ?',
                    implode(',', array_filter($filter_event_types)),
                    $slack_webhook['id']
                );
            }
        }
    }
}
