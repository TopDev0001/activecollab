<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Trash\Sections;

function tracking_handle_on_trash_sections(Sections &$sections, User $user)
{
    if ($user->isOwner() || ($user->isMember() && $project_ids = $user->getProjectIds())) {
        $parents = [];

        if ($rows = DB::execute('(SELECT DISTINCT parent_type, parent_id FROM time_records WHERE is_trashed = ?) UNION (SELECT DISTINCT parent_type, parent_id FROM expenses WHERE is_trashed = ?)', true, true)) {
            foreach ($rows as $row) {
                if (empty($parents[$row['parent_type']])) {
                    $parents[$row['parent_type']] = [];
                }

                $parents[$row['parent_type']][$row['parent_id']] = '--'; // Lets start with an unknown name
            }

            foreach (['Project' => 'projects', 'Task' => 'tasks'] as $type => $table) {
                if (isset($parents[$type]) && is_foreachable($parents[$type]) && $rows = DB::execute("SELECT id, name, is_trashed FROM $table WHERE id IN (?)", array_keys($parents[$type]))) {
                    foreach ($rows as $row) {
                        $parents[$type][$row['id']] = [
                            'name' => $row['name'],
                            'is_trashed' => $row['is_trashed'],
                        ];
                    }
                }
            }
        }

        foreach (['TimeRecord' => 'time_records', 'Expense' => 'expenses'] as $object_type => $table) {
            $id_name_map = [];

            if ($user->isOwner()) {
                $rows = DB::execute("SELECT id, parent_type, parent_id, summary FROM $table WHERE is_trashed = ? ORDER BY trashed_on DESC", true);
            } else {
                $rows = DB::execute("SELECT id, parent_type, parent_id, summary FROM $table WHERE trashed_by_id = ? AND is_trashed = ? ORDER BY trashed_on DESC", $user->getId(), true);
            }

            if ($rows) {
                foreach ($rows as $row) {
                    $parent_type = $row['parent_type'];
                    $parent_id = $row['parent_id'];

                    // skip item if it's parent is trashed
                    if (!empty($parents[$parent_type][$parent_id]['is_trashed'])) {  // "0" (0 as a string) is considered to be empty. ref: http://php.net/manual/en/function.empty.php
                        continue;
                    }

                    $name = isset($parents[$parent_type]) && isset($parents[$parent_type][$parent_id]['name']) ? $parents[$parent_type][$parent_id]['name'] : '';

                    if ($row['summary']) {
                        $name = $name ? "$name - $row[summary]" : $row['summary'];
                    }

                    if (empty($name)) {
                        $name = '--';
                    }

                    $id_name_map[$row['id']] = $name;
                }
            }

            if (count($id_name_map)) {
                $sections->registerTrashedObjects($object_type, $id_name_map, Sections::SECOND_WAVE);
            }
        }
    }
}
