<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Trash\Sections;

function files_handle_on_trash_sections(Sections &$sections, User $user)
{
    $trashed_project_ids = DB::executeFirstColumn('SELECT id FROM projects WHERE is_trashed = ?', true);
    if (empty($trashed_project_ids)) {
        $additional_conditions = '';
    } else {
        $additional_conditions = DB::prepare('AND project_id NOT IN (?)', $trashed_project_ids);
    }

    if ($user->isOwner()) {
        $id_name_map = DB::executeIdNameMap(
            'SELECT f.id, f.name FROM files AS f
                INNER JOIN projects AS p ON p.id = f.project_id AND p.is_trashed = ?
                WHERE f.is_trashed = ?
                ORDER BY f.trashed_on DESC',
            false,
            true
        );
    } elseif ($user->isMember() && $project_ids = $user->getProjectIds()) {
        $id_name_map = DB::executeIdNameMap(
            'SELECT f.id, f.name FROM files AS f
                INNER JOIN projects AS p ON p.id = f.project_id AND p.is_trashed = ?
                WHERE f.project_id IN (?) AND f.trashed_by_id = ? AND f.is_trashed = ?
                ORDER BY f.trashed_on DESC',
            false,
            $project_ids,
            $user->getId(),
            true
        );
    }

    if (!empty($id_name_map)) {
        $sections->registerTrashedObjects('File', $id_name_map);
    }
}
