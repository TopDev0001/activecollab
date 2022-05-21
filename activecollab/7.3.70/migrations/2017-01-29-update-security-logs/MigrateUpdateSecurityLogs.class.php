<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateSecurityLogs extends AngieModelMigration
{
    public function up()
    {
        $security_logs = $this->useTableForAlter('security_logs');

        $this->execute('DELETE FROM security_logs WHERE event_on < ?', DateTimeValue::makeFromString('-1 year'));
        $this->execute('DELETE FROM security_logs WHERE user_id IS NULL OR user_id = ?', 0);

        $security_logs->alterColumn(
            'event',
            new DBEnumColumn('event', ['login_attempt', 'login', 'logout', 'expired', 'failed'])
        );

        $this->execute('DELETE FROM security_logs WHERE event = ?', 'expired');
        $this->execute('UPDATE security_logs SET event = ? WHERE event = ?', 'login_attempt', 'failed');

        $security_logs->alterColumn(
            'event',
            new DBEnumColumn('event', ['login_attempt', 'login', 'logout']),
            'id'
        );

        foreach (['login_as_id', 'login_as_name', 'login_as_email', 'logout_by_id', 'logout_by_name', 'logout_by_email'] as $column) {
            $security_logs->dropColumn($column);
        }

        if ($security_logs->getIndex('event_on')) {
            $security_logs->dropIndex('event_on');
        }

        $security_logs->alterColumn(
            'event_on',
            new DBDateTimeColumn('created_on'),
            'user_agent'
        );

        foreach (['event', 'user_id', 'created_on', 'user_ip'] as $column_name) {
            if (!$security_logs->indexExists($column_name)) {
                $security_logs->addIndex(DBIndex::create($column_name));
            }
        }
    }
}
