<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Initial user settings collection.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class InitialUserSettingsCollection extends FwInitialUserSettingsCollection
{
    protected function onLoadSettings(array &$settings, User $user)
    {
        $options = [
            'display_mode_projects',
            'display_mode_completed_projects',
            'display_mode_project_templates',
            'project_groups_collapsed',
            'display_mode_project_files',
            'display_mode_project_tasks',
            'display_mode_project_time',
            'display_mode_invoices',
            'display_mode_estimates',
            'group_mode_people',
            'sort_mode_projects',
            'sort_mode_project_groups',
            'sort_mode_completed_projects',
            'group_mode_projects',
            'filter_projects_client',
            'filter_projects_label',
            'filter_projects_category',
            'filter_projects_leader',
            'filter_completed_projects_client',
            'filter_completed_projects_label',
            'filter_completed_projects_category',
            'filter_completed_projects_leader',
            'sort_mode_project_notes',
            'default_project_label_id',
            'my_work_projects_order',
            'show_visual_editor_toolbar',
            'filter_client_projects',
            'filter_label_projects',
            'filter_category_projects',
            'updates_hide_notifications',
            'browser_notifications',
            'desktop_notifications',
            'updates_play_sound',
            'search_sort_preference',
            'time_record_description_expanded',
            'my_work_activity_filter',
            'global_activity_filter',
            'project_activity_filter',
            'main_menu_options_order',
            'hidden_main_menu_options',
            'main_menu_expanded_projects',
            'main_menu_shortcuts_expanded',
        ];

        $values = ConfigOptions::getValuesFor($options, $user);

        foreach ($options as $option) {
            $settings[$option] = $values[$option];
        }

        if (empty($settings['my_work_projects_order'])) {
            $settings['my_work_projects_order'] = [];
        }
    }

    protected function onLoadCollections(array &$collections, User $user)
    {
        $collections['users'] = Users::prepareCollection(DataManager::ALL, $user);
        $collections['companies'] = Companies::prepareCollection(DataManager::ALL, $user);
        $collections['projects'] = Projects::prepareCollection('active_projects_page_1', $user);
        $collections['system_notifications'] = SystemNotifications::prepareCollection('active_recipient_system_notifications', $user);
    }
}
