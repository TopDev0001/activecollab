<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateMoveWebhooksToDedicatedTable extends AngieModelMigration
{
    public function up()
    {
        $webhooks_integration_id = $this->getWebhooksIntegration();
        $this->createNewStorage();
        $this->moveToNewStorage($webhooks_integration_id);
        $this->deleteFromOldStorage();
    }

    /**
     * Create new table for webhooks.
     *
     * @throws InvalidParamError
     */
    private function createNewStorage()
    {
        if (!$this->tableExists('webhooks')) {
            $this->createTable('webhooks', [
                new DBIdColumn(),
                DBFkColumn::create('integration_id'),
                new DBNameColumn(100),
                DBStringColumn::create('url'),
                new DBBoolColumn('is_enabled'),
                DBStringColumn::create('secret'),
                new DBTextColumn('filter_event_types'),
                new DBTextColumn('filter_projects'),
                new DBCreatedOnByColumn(),
            ]);
        }
    }

    /**
     * Find and return WebhooksIntegration id, or create the integration and return its id.
     *
     * @return int
     * @throws InvalidParamError
     */
    private function getWebhooksIntegration()
    {
        $webhooks_integration_id = DB::executeFirstCell('SELECT id FROM integrations WHERE type = ?', 'WebhooksIntegration');
        if ($webhooks_integration_id) {
            return (int) $webhooks_integration_id;
        } else {
            [$owner_id, $owner_name, $owner_email] = $this->getFirstUsableOwner();
            DB::execute('INSERT INTO integrations (type, created_on, created_by_id, created_by_email, created_by_name) VALUES (?, ?, ?, ?, ?)', 'WebhooksIntegration', DateTimeValue::now()->toMySQL(), $owner_id, $owner_email, $owner_name);

            return DB::lastInsertId();
        }
    }

    /**
     * Move existing webhook integrations to new storage and associate with newly created integartion (WebhooksIntegration).
     *
     * @param  int               $webhooks_integration_id
     * @throws InvalidParamError
     */
    private function moveToNewStorage($webhooks_integration_id)
    {
        if ($webhook_integrations = DB::execute('SELECT * FROM integrations WHERE type = ?', 'WebhookIntegration')) {
            foreach ($webhook_integrations as $row) {
                $properties = unserialize($row['raw_additional_properties']);
                DB::execute('INSERT INTO webhooks (integration_id, url, name, secret, is_enabled, created_on, created_by_id, created_by_email, created_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $webhooks_integration_id, $properties['url'], $properties['label'], $properties['secret'], $properties['is_enabled'], $row['created_on'], $row['created_by_id'], $row['created_by_email'], $row['created_by_name']);
            }
        }
    }

    /**
     * Remove webhook integrations from old storage.
     *
     * @throws InvalidParamError
     */
    private function deleteFromOldStorage()
    {
        DB::execute('DELETE FROM integrations WHERE type = ?', 'WebhookIntegration');
    }
}
