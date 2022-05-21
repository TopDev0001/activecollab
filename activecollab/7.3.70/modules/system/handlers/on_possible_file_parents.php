<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_possible_file_parents event handler.
 *
 * @package activeCollab.modules.system
 * @subpackage handlers
 */

/**
 * Handle on_possible_file_parents event.
 *
 * @param array $possible_parents
 * @param File  $file
 * @param null  $context
 * @param User  $user
 */
function system_handle_on_possible_file_parents(&$possible_parents, File $file, $context, $user)
{
    $projects = Projects::findActiveByUser($user, true);

    if ($projects) {
        $possible_parents['Project'] = [
            'label' => lang('Project'),
            'picker_label' => lang('Select Target Project'),
            'possibilities' => [],
        ];

        foreach ($projects as $project) {
            if (Files::canAdd($user, $project)) {
                $possible_parents['Project']['possibilities'][$project->getId()] = $project->getName();
            }
        }

        if (empty($possible_parents['Project']['possibilities'])) {
            unset($possible_parents['Project']);
        }
    }
}
