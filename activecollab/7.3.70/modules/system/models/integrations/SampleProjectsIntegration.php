<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\ProjectImport\Importer\SampleProjectImport;

class SampleProjectsIntegration extends Integration
{
    public function canView(User $user): bool
    {
        return $user->isPowerUser();
    }

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return !empty($this->getAdditionalProperty('status'));
    }

    public function getName()
    {
        return 'Sample Projects';
    }

    public function getShortName(): string
    {
        return 'sample-projects';
    }

    public function getDescription()
    {
        return lang('Import ActiveCollab Sample Projects');
    }

    /**
     * Get open action name.
     *
     * @return string
     */
    public function getOpenActionName()
    {
        return lang('Import');
    }

    /**
     * Get group of this integration.
     *
     * @return string
     */
    public function getGroup()
    {
        return 'migration_tools';
    }

    public function getGroupOrder()
    {
        return 1;
    }

    /**
     * Get all sample projects.
     *
     * @param  string $projects_list_path
     * @return array
     */
    public function getSampleProjects($projects_list_path = '')
    {
        if ($projects_list_path === '') {
            $projects_list_path = sprintf(
                '%s/modules/system/resources/sample_projects/sample_projects_list.json',
                APPLICATION_PATH
            );
        }

        if (!is_file($projects_list_path)) {
            return [];
        }

        $projects = json_decode(
            file_get_contents($projects_list_path),
            true
        );

        if (empty($projects) || !is_array($projects)) {
            $projects = [];
        }

        $active_projects = [];

        foreach ($projects as $key => $project) {
            if ($project['is_active']) {
                $active_projects[$key] = $project;
            }
        }

        return $active_projects;
    }

    /**
     * Import sample project.
     *
     * @param  string  $project_key
     * @return Project
     */
    public function import($project_key, User $user)
    {
        return (new SampleProjectImport($project_key, $user))->import();
    }
}
