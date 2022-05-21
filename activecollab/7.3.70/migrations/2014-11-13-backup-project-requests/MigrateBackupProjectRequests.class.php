<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Backup project requests to a project.
 *
 * @package ActiveCollab.migrations
 */
class MigrateBackupProjectRequests extends AngieModelMigration
{
    /**
     * @var array
     */
    private $enabled_custom_fields = false;

    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('project_requests')) {
            $project_requests = $this->useTables('project_requests')[0];

            if ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $project_requests")) {
                [$tasks, $comments, $projects, $project_users] = $this->useTables('tasks', 'comments', 'projects', 'project_users');
                [$owner_id, $owner_name, $owner_email] = $this->getFirstUsableOwner();

                $escaped_owner_id = DB::escape($owner_id);

                if ($this->execute("INSERT INTO $projects (slug, mail_to_project_code, company_id, name, leader_id, leader_name, leader_email, created_on, created_by_id, created_by_name, created_by_email, updated_on) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?, ?, UTC_TIMESTAMP())", $this->getSlug($projects), $this->getMailToProjectCode($projects), $this->getOwnerCompanyId(), 'Project Requests', $owner_id, $owner_name, $owner_email, $owner_id, $owner_name, $owner_email)) {
                    $project_id = $this->lastInsertId();
                    $escaped_project_id = DB::escape($project_id);
                    $task_id = 1;

                    $this->execute("INSERT INTO $project_users (project_id, user_id) VALUES ($escaped_project_id, $escaped_owner_id)");

                    foreach ($this->execute("SELECT * FROM $project_requests ORDER BY created_on") as $row) {
                        $this->execute("INSERT INTO $tasks (project_id, name, body, created_on, created_by_id, created_by_name, created_by_email, updated_on, task_id) VALUES ($escaped_project_id, ?, ?, ?, ?, ?, ?, ?, ?)", $row['name'], $this->getBodyFromRow($row), $row['created_on'], (int) $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['created_on'], $task_id++);
                        $this->execute("UPDATE $comments SET parent_type = 'Task', parent_id = ? WHERE parent_type = 'ProjectRequest' AND parent_id = ?", $this->lastInsertId(), $row['id']);
                    }
                }
            }

            $this->doneUsingTables();
        }

        $this->dropTable('project_requests');

        $this->removeConfigOption('project_requests_enabled');
        $this->removeConfigOption('project_requests_page_title');
        $this->removeConfigOption('project_requests_page_description');
        $this->removeConfigOption('project_requests_custom_fields');
        $this->removeConfigOption('project_requests_captcha_enabled');
        $this->removeConfigOption('project_requests_notify_user_ids');
    }

    /**
     * @param  string $projects_table
     * @return string
     */
    private function getSlug($projects_table)
    {
        $slug = 'project-requests';
        $counter = 1;

        while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $projects_table WHERE slug = ?", $slug)) {
            $slug = 'project-requests-' . $counter++;
        }

        return $slug;
    }

    /**
     * @param  string $projects_table
     * @return string
     */
    private function getMailToProjectCode($projects_table)
    {
        do {
            $mail_to_project_code = make_string(7);
        } while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $projects_table WHERE mail_to_project_code = ?", $mail_to_project_code));

        return $mail_to_project_code;
    }

    /**
     * @return int
     */
    private function getOwnerCompanyId()
    {
        return $this->executeFirstCell('SELECT id FROM ' . $this->useTables('companies')[0] . ' WHERE is_owner = ? ORDER BY id DESC LIMIT 0, 1', true);
    }

    /**
     * Prepare and return request body.
     *
     * @param  array             $row
     * @return string
     * @throws InvalidParamError
     */
    private function getBodyFromRow($row)
    {
        $body = $row['body'];

        // Created By
        $body .= '<p><b>Created By</b>: ';

        if ($row['created_by_name']) {
            $body .= '<a href="mailto:' . clean($row['created_by_email']) . '">' . clean($row['created_by_name']) . '</a>';
        } else {
            $body .= '<a href="mailto:' . clean($row['created_by_email']) . '">' . clean($row['created_by_email']) . '</a>';
        }

        $body .= '</p>';

        [$company_id, $company_name, $company_address] = $this->getCompanyFromRow($row);

        // Company
        $body .= '<p><b>For</b>: ' . clean($company_name);

        if ($company_id) {
            $body .= ' (saved in People section)';
        }

        if ($company_address) {
            $body .= '<br><br>' . nl2br(clean($company_address));
        }

        $body .= '</p>';

        // Custom fields
        foreach ($this->getEnabledCustomFields() as $field => $label) {
            $body .= '<p><b>' . clean($label) . '</b>: ' . clean($row[$field]) . '</p>';
        }

        // Taken by
        if ($row['taken_by_name']) {
            $body .= '<p><b>Taken By</b>: ' . clean($row['taken_by_name']) . '</p>';
        }

        return $body;
    }

    /**
     * @param  array $row
     * @return array
     */
    private function getCompanyFromRow($row)
    {
        if ($row['created_by_company_id']) {
            if ($company_row = $this->executeFirstRow('SELECT id, name, address FROM ' . $this->useTables('companies')[0] . ' WHERE id = ?', $row['created_by_company_id'])) {
                return [$company_row['id'], $company_row['name'], $company_row['address']];
            }
        }

        return [0, $row['created_by_company_name'], $row['created_by_company_address']];
    }

    /**
     * Get an array of enabled project request custom fields.
     *
     * @return array
     */
    private function getEnabledCustomFields()
    {
        if ($this->enabled_custom_fields === false) {
            $this->enabled_custom_fields = [];

            $project_requests_custom_fields = $this->getConfigOptionValue('project_requests_custom_fields');

            if ($project_requests_custom_fields && is_foreachable($project_requests_custom_fields)) {
                foreach ($project_requests_custom_fields as $name => $properties) {
                    if ($properties['enabled']) {
                        $this->enabled_custom_fields[$name] = isset($properties['name']) && trim($properties['name']) ? trim($properties['name']) : $name;
                    }
                }
            }
        }

        return $this->enabled_custom_fields;
    }
}
