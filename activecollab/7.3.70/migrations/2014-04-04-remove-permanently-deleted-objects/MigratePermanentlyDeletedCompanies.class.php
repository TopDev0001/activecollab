<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate permanently deleted companies.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigratePermanentlyDeletedCompanies extends AngieModelMigration
{
    /**
     * Execute after.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePermanentlyDeletedProjects');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        [$companies] = $this->useTables('companies');

        defined('STATE_DELETED') or define('STATE_DELETED', 0);

        if ($company_ids = $this->executeFirstColumn("SELECT id FROM $companies WHERE state = ? AND is_owner = ?", STATE_DELETED, false)) {
            $this->execute("DELETE FROM $companies WHERE id IN (?)", $company_ids);
            $this->updateProjectClients();
        }

        $this->doneUsingTables();
    }

    /**
     * Update company clients so owner is a client for all non-assigned project.
     */
    private function updateProjectClients()
    {
        [$companies, $projects] = $this->useTables('companies', 'projects');

        $company_ids = $this->executeFirstColumn("SELECT id FROM $companies");
        $owner_company_id = (int) $this->executeFirstCell("SELECT id FROM $companies WHERE is_owner = ? LIMIT 0, 1", true);

        $this->execute("UPDATE $projects SET company_id = ? WHERE company_id NOT IN (?)", $owner_company_id, $company_ids);
    }
}
