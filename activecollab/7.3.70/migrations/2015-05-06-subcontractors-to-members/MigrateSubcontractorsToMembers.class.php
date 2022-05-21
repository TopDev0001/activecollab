<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate subcontractors to members.
 *
 * @package ActiveCollab.migrations
 */
class MigrateSubcontractorsToMembers extends AngieModelMigration
{
    /**
     * Construct and make sure that this is executed after members have been moved to the owner company.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateMembersToOwnerCompany');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        [$activity_logs, $companies, $modification_logs, $users] = $this->useTables('activity_logs', 'companies', 'modification_logs', 'users');

        if ($subcontractor_ids = $this->executeFirstColumn("SELECT id FROM $users WHERE type = 'Subcontractor'")) {
            if ($owner_company_id = $this->executeFirstCell("SELECT id FROM $companies WHERE is_owner = ? LIMIT 0, 1", true)) {
                $this->execute("UPDATE $users SET company_id = '0' WHERE type = 'Subcontractor' AND company_id = ?", $owner_company_id);
            }

            $this->execute("UPDATE $activity_logs SET parent_type = 'Member' WHERE parent_type = 'Subcontractor'");
            $this->execute("UPDATE $modification_logs SET parent_type = 'Member' WHERE parent_type = 'Subcontractor'");

            $this->execute("UPDATE $users SET type = 'Member' WHERE type = 'Subcontractor'");
        }

        $this->doneUsingTables();
    }
}
