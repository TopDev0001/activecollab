<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Trash\Sections;

function notes_handle_on_trash_sections(Sections &$sections, User $user)
{
    if ($user->isOwner()) {
        $id_name_map = DB::executeIdNameMap(
            'SELECT n.id, n.name FROM notes AS n
                INNER JOIN projects AS p ON p.id = n.project_id AND p.is_trashed = ?
                WHERE n.is_trashed = ?
                ORDER BY n.trashed_on DESC',
            false,
            true
        );
    } elseif ($user->isMember() && $project_ids = $user->getProjectIds()) {
        $id_name_map = DB::executeIdNameMap(
            'SELECT n.id, n.name FROM notes AS n
                INNER JOIN projects AS p ON p.id = n.project_id AND  p.is_trashed = ?
                WHERE n.project_id IN (?) AND n.trashed_by_id = ? AND n.is_trashed = ?
                ORDER BY n.trashed_on DESC',
            false,
            $project_ids,
            $user->getId(),
            true
        );
    }

    if (!empty($id_name_map)) {
        $sections->registerTrashedObjects('Note', $id_name_map);
    }
}
