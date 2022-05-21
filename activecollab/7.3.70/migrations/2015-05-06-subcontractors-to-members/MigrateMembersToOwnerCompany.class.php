<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate members to the owner company.
 *
 * @package ActiveCollab.migrations
 */
class MigrateMembersToOwnerCompany extends AngieModelMigration
{
    /**
     * Companies to teams map.
     *
     * @var array
     */
    private $company_to_team_map = [];
    /**
     * @var array
     */
    private $team_created_by = false;

    /**
     * Migrate up.
     */
    public function up()
    {
        [$companies, $teams, $team_users, $users] = $this->useTables('companies', 'teams', 'team_users', 'users');

        if ($owner_company_id = $this->executeFirstCell("SELECT id FROM $companies WHERE is_owner = ? LIMIT 0, 1", true)) {
            if ($team_members = $this->execute("SELECT id, company_id FROM $users WHERE type = 'Member' AND company_id != ?", $owner_company_id)) {
                foreach ($team_members as $team_member) {
                    if ($team_id = $this->companyToTeamId($companies, $teams, $team_member['company_id'])) {
                        $this->execute("INSERT INTO $team_users (team_id, user_id) VALUES (?, ?)", $team_id, $team_member['id']);
                    }

                    $this->execute("UPDATE $users SET type = 'Member', company_id = ? WHERE id = ?", $owner_company_id, $team_member['id']);
                }
            }

            if (count($this->company_to_team_map)) {
                [$activity_logs, $custom_hourly_rates, $modification_logs, $modification_log_values] = $this->useTables('activity_logs', 'custom_hourly_rates', 'modification_logs', 'modification_log_values');

                $tables_to_check = array_merge($this->useTables('invoices', 'recurring_profiles', 'estimates', 'projects'), [$users]);

                foreach (array_keys($this->company_to_team_map) as $company_id) {
                    $counts = 0;

                    foreach ($tables_to_check as $table_to_check) {
                        $counts += $this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $table_to_check WHERE company_id = ?", $company_id);
                    }

                    if ($counts === 0) {
                        $this->execute("DELETE FROM $activity_logs WHERE parent_type = 'Company' AND parent_id = ?", $company_id);
                        $this->execute("DELETE FROM $custom_hourly_rates WHERE parent_type = 'Company' AND parent_id = ?", $company_id);

                        if ($modification_ids = $this->executeFirstColumn("SELECT id FROM $modification_logs WHERE parent_type = 'Company' AND parent_id = ?", $company_id)) {
                            $this->execute("DELETE FROM $modification_logs WHERE id IN (?)", $modification_ids);
                            $this->execute("DELETE FROM $modification_log_values WHERE modification_id IN (?)", $modification_ids);
                        }

                        $this->execute("DELETE FROM $companies WHERE id = ?", $company_id);
                    }
                }
            }
        }

        $this->doneUsingTables();
    }

    /**
     * Map company by ID with a team.
     *
     * @param  string $companies
     * @param  string $teams
     * @param  int    $company_id
     * @return int
     */
    private function companyToTeamId($companies, $teams, $company_id)
    {
        if ($company_id) {
            if (empty($this->company_to_team_map[$company_id])) {
                if ($company_name = trim($this->executeFirstCell("SELECT name FROM $companies WHERE id = ?", $company_id))) {
                    $this->execute("INSERT INTO $teams (name, created_by_id, created_by_name, created_by_email, created_on, updated_on) VALUES (?, ?, ?, ?, NOW(), NOW())", $this->getUniqueTeamName($company_name, $teams), $this->getTeamCreatedByData()[0], $this->getTeamCreatedByData()[1], $this->getTeamCreatedByData()[2]);
                    $this->company_to_team_map[$company_id] = $this->lastInsertId();
                } else {
                    $this->company_to_team_map[$company_id] = 0; // Company with the given ID was not found
                }
            }

            return $this->company_to_team_map[$company_id];
        }

        return 0;
    }

    /**
     * Return unique team name based on company name.
     *
     * @param  string $company_name
     * @param  string $teams
     * @return string
     */
    private function getUniqueTeamName($company_name, $teams)
    {
        $name = '';
        $counter = 1;

        do {
            $name = empty($name) ? $company_name : $company_name . ' ' . $counter++;
        } while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $teams WHERE name = ?", $name));

        return $name;
    }

    /**
     * Get owner info for created_by data of newly created teams.
     *
     * @return array
     */
    private function &getTeamCreatedByData()
    {
        if ($this->team_created_by === false) {
            $this->team_created_by = $this->getFirstUsableOwner();
        }

        return $this->team_created_by;
    }
}
