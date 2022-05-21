<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Trash\Sections;

function discussions_handle_on_trash_sections(Sections &$sections, User $user)
{
    if ($user->isOwner()) {
        $id_name_map = DB::executeIdNameMap(
            'SELECT d.id, d.name FROM discussions AS d
                INNER JOIN projects AS p ON p.id = d.project_id AND p.is_trashed = ?
                WHERE d.is_trashed = ?
                ORDER BY d.trashed_on DESC',
            false,
            true
        );
    } elseif ($user->isMember() && $project_ids = $user->getProjectIds()) {
        $id_name_map = DB::executeIdNameMap(
            'SELECT d.id, d.name FROM discussions AS d
                INNER JOIN projects AS p ON p.id = d.project_id AND p.is_trashed = ?
                WHERE d.project_id IN (?) AND d.trashed_by_id = ? AND d.is_trashed = ?
                ORDER BY d.trashed_on DESC',
            false,
            $project_ids,
            $user->getId(),
            true
        );
    }

    if (!empty($id_name_map)) {
        $sections->registerTrashedObjects('Discussion', $id_name_map);
    }
}
