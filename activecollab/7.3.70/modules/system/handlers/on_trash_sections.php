<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Inflector;
use Angie\Trash\Sections;

function system_handle_on_trash_sections(Sections &$sections, User $user)
{
    if ($user->isOwner()) {
        $sections->registerTrashedObjects(User::class, DB::executeIdNameMap('SELECT id, TRIM(CONCAT(first_name, " ", last_name)) AS "full_name", email FROM users WHERE is_trashed = ? ORDER BY trashed_on DESC', true, function ($row) {
            return Users::getUserDisplayName($row);
        }), Sections::THIRD_WAVE);
    } elseif ($user->isPowerUser()) {
        $sections->registerTrashedObjects(User::class, DB::executeIdNameMap('SELECT id, TRIM(CONCAT(first_name, " ", last_name)) AS "full_name", email FROM users WHERE is_trashed = ? AND trashed_by_id = ? ORDER BY trashed_on DESC', true, $user->getId(), function ($row) {
            return Users::getUserDisplayName($row);
        }), Sections::THIRD_WAVE);
    }

    $companies_id_name_map = null;
    $projects_id_name_map = null;
    $projects_template_id_name_map = null;

    if ($user->isOwner()) {
        $companies_id_name_map = DB::executeIdNameMap('SELECT id, name FROM companies WHERE is_trashed = ? ORDER BY trashed_on DESC', true);
        $projects_id_name_map = DB::executeIdNameMap('SELECT id, name FROM projects WHERE is_trashed = ? ORDER BY trashed_on DESC', true);
    } elseif ($user->isPowerUser()) {
        $projects_id_name_map = DB::executeIdNameMap('SELECT id, name FROM projects WHERE trashed_by_id = ? AND is_trashed = ? ORDER BY trashed_on DESC', $user->getId(), true);
    }

    if ($user->isOwner() || $user->isPowerUser()) {
        $projects_template_id_name_map = DB::executeIdNameMap('SELECT id, name FROM project_templates WHERE trashed_by_id = ? AND is_trashed = ? ORDER BY trashed_on DESC', $user->getId(), true);
    }

    $sections->registerTrashedObjects(Company::class, $companies_id_name_map, Sections::FOURTH_WAVE);
    $sections->registerTrashedObjects(Project::class, $projects_id_name_map, Sections::THIRD_WAVE);
    $sections->registerTrashedObjects(ProjectTemplate::class, $projects_template_id_name_map, Sections::FOURTH_WAVE);

    $row_to_name = function ($row) {
        return substr_utf(str_replace("\n", ' ', Angie\HTML::toPlainText($row['body'])), 0, 50);
    };

    $trashed_parents = [];

    // get ids for all trashed items of parent type
    if ($parent_types = DB::executeFirstColumn('SELECT DISTINCT parent_type FROM comments WHERE is_trashed = ?', true)) {
        foreach ($parent_types as $parent_type) {
            // @TODO Remove this debugging block once we clean up NotebookPage comments
            if (strtolower($parent_type) == 'notebookpage') {
                AngieApplication::log()->error('Found {num} NotebookPage instances in comments table', [
                    'num' => DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM comments WHERE parent_type = ?', $parent_type),
                ]);

                continue;
            }

            $parent_table = Inflector::pluralize(Inflector::underscore($parent_type));

            if ($trashed_parent_ids = DB::executeFirstColumn('SELECT id FROM ' . $parent_table . ' WHERE is_trashed = ?', true)) {
                $trashed_parents[$parent_table] = $trashed_parent_ids;
            }
        }
    }

    if (is_foreachable($trashed_parents)) {
        $parent_conditions = [];

        foreach ($trashed_parents as $type => $ids) {
            $parent_type = Inflector::singularize(Inflector::camelize($type));
            $parent_conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $parent_type, $ids);
        }
        // prepare additional comments string
        $additional_conditions = 'AND NOT (' . implode(' OR ', $parent_conditions) . ')';
    } else {
        $additional_conditions = '';
    }

    if ($user->isOwner()) {
        $id_name_map = DB::executeIdNameMap('SELECT id, body FROM comments WHERE is_trashed = ? ' . $additional_conditions . ' ORDER BY trashed_on DESC', true, $row_to_name);
    } elseif ($user->isMember()) {
        $id_name_map = DB::executeIdNameMap('SELECT id, body FROM comments WHERE trashed_by_id = ? AND is_trashed = ? ' . $additional_conditions . ' ORDER BY trashed_on DESC', $user->getId(), true, $row_to_name);
    }

    if (!empty($id_name_map)) {
        $sections->registerTrashedObjects(Comment::class, $id_name_map, Sections::SECOND_WAVE);
    }
}
