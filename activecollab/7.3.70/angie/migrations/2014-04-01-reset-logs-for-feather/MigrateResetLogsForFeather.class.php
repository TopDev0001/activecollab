<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateResetLogsForFeather extends AngieModelMigration
{
    public function up()
    {
        $this->resetAccessLogs();
        $this->resetActivityLogs();
        $this->resetMailingLog();
        $this->resetModificationLog();
        $this->resetNotifications();
        $this->resetObjectContexts();

        $this->doneUsingTables();
    }

    private function resetAccessLogs()
    {
        foreach ($this->useTables('access_logs') as $table) {
            $this->execute("TRUNCATE TABLE $table");
        }

        $this->dropTable('access_logs_archive');
    }

    private function resetActivityLogs()
    {
        $this->dropTable('activity_logs');
    }

    private function resetMailingLog()
    {
        foreach ($this->useTables('mailing_activity_logs', 'outgoing_messages') as $table) {
            $this->execute("TRUNCATE TABLE $table");
        }

        $this->execute('DELETE FROM ' . $this->useTables('attachments')[0] . " WHERE parent_type = 'OutgoingMessage'");
    }

    private function resetModificationLog()
    {
        $logs = $this->useTableForAlter('modification_logs');
        $values = $this->useTableForAlter('modification_log_values');

        $this->execute('TRUNCATE TABLE ' . $logs->getName());
        $this->execute('TRUNCATE TABLE ' . $values->getName());

        $logs->dropColumn('is_first');

        $values->dropColumn('value');
        $values->addColumn(
            (new DBTextColumn('old_value'))
                ->setSize(DBColumn::BIG),
            'field'
        );
        $values->addColumn(
            (new DBTextColumn('new_value'))
                ->setSize(DBColumn::BIG),
            'old_value'
        );
    }

    private function resetNotifications()
    {
        foreach ($this->useTables('notifications', 'notification_recipients') as $table) {
            $this->execute("TRUNCATE TABLE $table");
        }
    }

    private function resetObjectContexts()
    {
        $this->dropTable('object_contexts');
    }
}
