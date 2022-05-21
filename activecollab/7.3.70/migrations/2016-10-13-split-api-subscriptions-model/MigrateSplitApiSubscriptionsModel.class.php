<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateSplitApiSubscriptionsModel extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigratePrepareEmptyLastLoginOn');
    }

    public function up()
    {
        $api_subscriptions = $this->useTableForAlter('api_subscriptions');

        // ---------------------------------------------------
        //  Add user sessions table
        // ---------------------------------------------------

        if (!$this->tableExists('user_sessions')) {
            $this->createTable(
                DB::createTable('user_sessions')->addColumns(
                    [
                        new DBIdColumn(),
                        DBFkColumn::create('user_id', 0, true),
                        DBStringColumn::create('session_id', 191),
                        DBIntegerColumn::create('session_ttl', 10, 0)->setUnsigned(true),
                        DBStringColumn::create('csrf_validator', 191),
                        new DBCreatedOnColumn(),
                        new DBDateTimeColumn('last_used_on'),
                        DBIntegerColumn::create('requests_count', 10, 1)->setUnsigned(true),
                    ]
                )->addIndices(
                    [
                        DBIndex::create('session_id', DBIndex::UNIQUE, 'session_id'),
                        DBIndex::create('csrf_validator'),
                    ]
                )
            );
        }

        // ---------------------------------------------------
        //  Migrate existing sessions
        // ---------------------------------------------------

        // Remove expired
        if ($ids = $this->executeFirstColumn("SELECT id, last_used_on + INTERVAL lifetime SECOND AS 'api_subscription_expires_on' FROM api_subscriptions WHERE lifetime > 0 HAVING api_subscription_expires_on < UTC_TIMESTAMP()")) {
            $this->execute('DELETE FROM api_subscriptions WHERE id IN (?)', $ids);
        }

        // Remove API subscriptions that have not been used is more than 6 months
        $this->execute('DELETE FROM api_subscriptions WHERE last_used_on < ?', DateTimeValue::makeFromString('-6 months'));

        // Move sessions
        if ($rows = $this->execute('SELECT user_id, token, csrf_validator, created_on, last_used_on, requests_count, lifetime FROM api_subscriptions WHERE lifetime > ?', 0)) {
            $batch_insert = new DBBatchInsert('user_sessions', ['user_id', 'session_id', 'csrf_validator', 'created_on', 'last_used_on', 'requests_count', 'session_ttl']);

            foreach ($rows as $row) {
                $batch_insert->insertArray(array_values($row));
            }

            $batch_insert->done();
        }

        $this->execute('DELETE FROM api_subscriptions WHERE lifetime > ?', 0);

        // ---------------------------------------------------
        //  Clean up API subscriptions table
        // ---------------------------------------------------

        $api_subscriptions->alterColumn('token', DBStringColumn::create('token_id', 191));
        $api_subscriptions->dropColumn('csrf_validator');
        $api_subscriptions->dropColumn('lifetime');
        $api_subscriptions->addIndex(new DBIndex('last_used_on'));
    }
}
