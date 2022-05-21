<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update project templates model for Feather.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateProjectTemplatesModelForFeather extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $templates = $this->useTableForAlter('project_templates');
        $template_objects = $this->useTableForAlter('project_object_templates');

        $this->updateTemplates($templates);
        $this->updateTemplateObjects($templates, $template_objects);

        $this->doneUsingTables();

        $this->renameTable('project_object_templates', 'project_template_elements');
    }

    private function updateTemplates(DBTable &$templates)
    {
        foreach (['category_id', 'company_id', 'created_on'] as $field) {
            if ($templates->indexExists($field)) {
                $templates->dropIndex($field);
            }
        }

        $templates->dropColumn('category_id');
        $templates->dropColumn('company_id');
        $templates->dropColumn('updated_by_id');
        $templates->dropColumn('updated_by_name');
        $templates->dropColumn('updated_by_email');
        $templates->dropColumn('custom_field_1');
        $templates->dropColumn('custom_field_2');
        $templates->dropColumn('custom_field_3');
        $templates->dropColumn('position');

        if (!$templates->indexExists('name')) {
            $templates->addIndex(DBIndex::create('name'));
        }
    }

    private function updateTemplateObjects(DBTable &$templates, DBTable &$template_objects)
    {
        $template_objects->alterColumn(
            'type',
            new DBTypeColumn('ProjectTemplateElement'),
            'id'
        );
        $template_objects->alterColumn('template_id', DBFkColumn::create('template_id'), 'type');
        $template_objects->addColumn(new DBNameColumn(), 'template_id');
        $template_objects->addColumn(
            (new DBTextColumn('body'))->setSize(DBTextColumn::BIG),
            'name'
        );
        $template_objects->alterColumn('value', new DBAdditionalPropertiesColumn(), 'subtype');

        $this->execute('UPDATE ' . $template_objects->getName() . ' SET position = ? WHERE position IS NULL', 0);

        $template_objects->alterColumn('position', DBIntegerColumn::create('position', 10, 0)->setUnsigned(true));

        $template_ids = $this->executeFirstColumn('SELECT id FROM ' . $templates->getName());

        if ($template_ids && is_foreachable($template_ids)) {
            $this->execute('DELETE FROM ' . $template_objects->getName() . ' WHERE template_id NOT IN (?)', $template_ids);

            $users = $this->updateUserRows($template_objects);
            $this->updateFileRows($template_objects);
            $task_categories = $this->updateCategoryRows($template_objects);
            $milestones = $this->updateMilestoneRows($template_objects);
            $tasks = $this->updateTaskRows($template_objects, $users, $milestones, $task_categories);
            $this->updateSubtaskRows($template_objects, $tasks);
        }

        $template_objects->dropColumn('parent_id');
        $template_objects->dropColumn('subtype');
        $template_objects->dropColumn('file_size');
    }

    /**
     * @return array
     */
    private function updateUserRows(DBTable &$template_objects)
    {
        $users = [];

        if ($user_rows = $this->execute('SELECT id, template_id, raw_additional_properties FROM ' . $template_objects->getName() . " WHERE type = 'Position'")) {
            $to_drop = [];

            foreach ($user_rows as $user_row) {
                $template_id = $user_row['template_id'];

                $attributes = $user_row['raw_additional_properties'] ? unserialize($user_row['raw_additional_properties']) : [];

                $attributes['user_id'] = isset($attributes['user_id']) ? (int) $attributes['user_id'] : 0;

                if (empty($attributes['user_id'])) {
                    $to_drop[] = $user_row['id'];

                    if (empty($users[$template_id])) {
                        $users[$template_id] = [];
                    }

                    if (!in_array($user_row['id'], $users[$template_id])) {
                        $users[$template_id][] = $user_row['id'];
                    }
                } else {
                    foreach (['name', 'project_template_permissions'] as $field) {
                        if (array_key_exists($field, $attributes)) {
                            unset($attributes[$field]);
                        }
                    }

                    $this->execute('UPDATE ' . $template_objects->getName() . " SET type = 'ProjectTemplateUser', name = '', body = '', raw_additional_properties = ? WHERE id = ?", serialize($attributes), $user_row['id']);
                }
            }

            if (count($to_drop)) {
                $this->execute('DELETE FROM ' . $template_objects->getName() . ' WHERE id IN (?)', $to_drop);
            }
        }

        return $users;
    }

    /**
     * Update file rows.
     *
     * @throws InvalidParamError
     */
    private function updateFileRows(DBTable &$template_objects)
    {
        if ($file_rows = $this->execute('SELECT * FROM ' . $template_objects->getName() . ' WHERE type = ?', 'File')) {
            foreach ($file_rows as $file_row) {
                $this->updateFileRowAttributes($file_row);
                $this->execute('UPDATE ' . $template_objects->getName() . " SET type = 'ProjectTemplateFile', name = ?, body = ?, raw_additional_properties = ?, position = '0' WHERE id = ?", $file_row['name'], $file_row['body'], $file_row['raw_additional_properties'], $file_row['id']);
            }
        }
    }

    /**
     * Update attributes of the individual subtask row.
     */
    private function updateFileRowAttributes(array &$file_row)
    {
        $attributes = $file_row['raw_additional_properties'] ? unserialize($file_row['raw_additional_properties']) : [];

        if (array_key_exists('name', $file_row)) {
            $file_row['name'] = $attributes['name'];

            unset($attributes['name']);
        } else {
            $file_row['name'] = 'unknown-file';
        }

        $file_row['body'] = '';

        foreach (['category_id', 'body', 'state', 'version'] as $field) {
            if (array_key_exists($field, $attributes)) {
                unset($attributes[$field]);
            }
        }

        $attributes['size'] = (int) $file_row['file_size'];

        $attributes['is_hidden_from_clients'] = array_key_exists('visibility', $attributes) && empty($attributes['visibility']);

        if (empty($attributes['is_hidden_from_clients'])) {
            unset($attributes['is_hidden_from_clients']);
        }

        $file_row['raw_additional_properties'] = count($attributes) ? serialize($attributes) : null;
    }

    /**
     * @return array
     */
    private function updateCategoryRows(DBTable &$template_objects)
    {
        $task_categories = [];

        if ($task_category_rows = $this->execute('SELECT id, raw_additional_properties FROM ' . $template_objects->getName() . " WHERE type = 'Category' AND subtype = 'task'")) {
            foreach ($task_category_rows as $task_category_row) {
                $attributes = $task_category_row['raw_additional_properties'] ? unserialize($task_category_row['raw_additional_properties']) : [];

                if (isset($attributes['name']) && trim($attributes['name'])) {
                    $task_categories[$task_category_row['id']] = trim($attributes['name']);
                }
            }
        }

        $this->execute('DELETE FROM ' . $template_objects->getName() . " WHERE type = 'Category'");

        return $task_categories;
    }

    /**
     * @return array
     */
    private function updateMilestoneRows(DBTable &$template_objects)
    {
        $milestones = [];

        if ($milestone_rows = $this->execute('SELECT id, raw_additional_properties FROM ' . $template_objects->getName() . ' WHERE type = ?', 'Milestone')) {
            foreach ($milestone_rows as $milestone_row) {
                $this->updateMilestoneRowAttributes($milestone_row);
                $this->execute('UPDATE ' . $template_objects->getName() . " SET type = 'ProjectTemplateMilestone', name = ?, body = ?, raw_additional_properties = ? WHERE id = ?", $milestone_row['name'], $milestone_row['body'], $milestone_row['raw_additional_properties'], $milestone_row['id']);

                $milestones[$milestone_row['id']] = $milestone_row['name'];
            }
        }

        return $milestones;
    }

    /**
     * Update attributes of the individual milestone row.
     */
    private function updateMilestoneRowAttributes(array &$milestone_row)
    {
        $attributes = $milestone_row['raw_additional_properties'] ? unserialize($milestone_row['raw_additional_properties']) : [];

        $milestone_row['name'] = isset($attributes['name']) && $attributes['name'] ? trim($attributes['name']) : '-- Unknown --';
        $milestone_row['body'] = '';

        foreach (['name', 'specify', 'assignee_id', 'other_assignees', 'body', 'priority'] as $field) {
            if (array_key_exists($field, $attributes)) {
                unset($attributes[$field]);
            }
        }

        $attributes['start_on'] = isset($attributes['start_on']) ? (int) $attributes['start_on'] : 0;
        $attributes['due_on'] = isset($attributes['due_on']) ? (int) $attributes['due_on'] : 0;

        if (empty($attributes['start_on']) || empty($attributes['due_on'])) {
            unset($attributes['start_on']);
            unset($attributes['due_on']);
        }

        $milestone_row['raw_additional_properties'] = count($attributes) ? serialize($attributes) : null;
    }

    /**
     * @return array
     */
    private function updateTaskRows(DBTable &$template_objects, array $users, array $milestones, array $categories)
    {
        $tasks = [];

        if ($task_rows = $this->execute('SELECT * FROM ' . $template_objects->getName() . ' WHERE type = ?', 'Task')) {
            foreach ($task_rows as $task_row) {
                $this->updateTaskRowAttributes($task_row, $users, $milestones, $categories);
                $this->execute('UPDATE ' . $template_objects->getName() . " SET type = 'ProjectTemplateTask', name = ?, body = ?, raw_additional_properties = ? WHERE id = ?", $task_row['name'], $task_row['body'], $task_row['raw_additional_properties'], $task_row['id']);

                $tasks[$task_row['id']] = [
                    'template_id' => $task_row['template_id'],
                    'name' => $task_row['name'],
                ];
            }
        }

        return $tasks;
    }

    /**
     * Update attributes of the individual task row.
     */
    private function updateTaskRowAttributes(array &$task_row, array $users, array $milestones, array $categories)
    {
        $attributes = $task_row['raw_additional_properties'] ? unserialize($task_row['raw_additional_properties']) : [];

        if (array_key_exists('name', $attributes)) {
            $task_row['name'] = trim($attributes['name']);
        } else {
            $task_row['name'] = 'Unknown';
        }

        if (array_key_exists('body', $attributes)) {
            $task_row['body'] = trim($attributes['body']);
        } else {
            $task_row['body'] = '';
        }

        if (array_key_exists('assignee_id', $attributes)) {
            $attributes['assignee_id'] = (int) $attributes['assignee_id'];

            if (empty($attributes['assignee_id'])) {
                unset($attributes['assignee_id']);
            }
        }

        if (array_key_exists('label_id', $attributes)) {
            $attributes['label_id'] = (int) $attributes['label_id'];

            if (empty($attributes['label_id'])) {
                unset($attributes['label_id']);
            }
        }

        if (array_key_exists('priority', $attributes)) {
            if ($attributes['priority'] > 0) {
                $attributes['is_important'] = true;
            }

            unset($attributes['priority']);
        }

        if ($task_row['parent_id'] && !empty($milestones[$task_row['parent_id']])) {
            $attributes['task_list_id'] = $task_row['parent_id'];
        } elseif (array_key_exists('milestone_id', $attributes)) {
            unset($attributes['milestone_id']);
        }

        if (array_key_exists('category_id', $attributes)) {
            if (isset($categories[$attributes['category_id']])) {
                $attributes['category_name'] = $categories[$attributes['category_id']];
            }

            unset($attributes['category_id']);
        }

        $attributes['estimate_value'] = isset($attributes['estimate_value']) && $attributes['estimate_value'] ? round((float) $attributes['estimate_value'], 2) : 0;
        $attributes['estimate_job_type_id'] = isset($attributes['estimate_job_type_id']) && $attributes['estimate_job_type_id'] ? (int) $attributes['estimate_job_type_id'] : 0;

        if (empty($attributes['estimate_value']) || empty($attributes['estimate_job_type_id'])) {
            unset($attributes['estimate_value']);
            unset($attributes['estimate_job_type_id']);
        }

        if (array_key_exists('visibility', $attributes)) {
            $attributes['is_hidden_from_clients'] = empty($attributes['visibility']);

            if (empty($attributes['is_hidden_from_clients'])) {
                unset($attributes['is_hidden_from_clients']);
            }

            unset($attributes['visibility']);
        }

        $attributes['due_on'] = isset($attributes['due_on']) ? (int) $attributes['due_on'] : 0;

        if (empty($attributes['due_on'])) {
            unset($attributes['due_on']);
        }

        foreach (['name', 'body', 'specify', 'other_assignees'] as $field) {
            if (array_key_exists($field, $attributes)) {
                unset($attributes[$field]);
            }
        }

        $task_row['raw_additional_properties'] = count($attributes) ? serialize($attributes) : null;
    }

    /**
     * Update subtask rows.
     *
     * @throws InvalidParamError
     */
    private function updateSubtaskRows(DBTable &$template_objects, array $tasks)
    {
        if ($subtask_rows = $this->execute('SELECT * FROM ' . $template_objects->getName() . " WHERE type = 'Subtask'")) {
            foreach ($subtask_rows as $subtask_row) {
                $task_id = (int) $subtask_row['parent_id'];

                if (empty($tasks[$task_id])) {
                    continue;
                }

                $this->updateSubtaskRowAttributes($subtask_row);
                $this->execute('UPDATE ' . $template_objects->getName() . " SET template_id = ?, type = 'ProjectTemplateSubtask', name = ?, body = ?, raw_additional_properties = ? WHERE id = ?", $tasks[$task_id]['template_id'], $subtask_row['name'], $subtask_row['body'], $subtask_row['raw_additional_properties'], $subtask_row['id']);
            }
        }
    }

    /**
     * Update attributes of the individual subtask row.
     */
    private function updateSubtaskRowAttributes(array &$subtask_row)
    {
        $attributes = $subtask_row['raw_additional_properties'] ? unserialize($subtask_row['raw_additional_properties']) : [];

        $subtask_row['name'] = '';

        if (array_key_exists('body', $attributes)) {
            $subtask_row['body'] = trim($attributes['body']);
        }

        if (empty($subtask_row['body'])) {
            $subtask_row['body'] = 'Unknown';
        }

        if (array_key_exists('assignee_id', $attributes)) {
            $attributes['assignee_id'] = (int) $attributes['assignee_id'];

            if (empty($attributes['assignee_id'])) {
                unset($attributes['assignee_id']);
            }
        }

        $attributes['due_on'] = isset($attributes['due_on']) ? (int) $attributes['due_on'] : 0;

        if (empty($attributes['due_on'])) {
            unset($attributes['due_on']);
        }

        if ($subtask_row['parent_id'] && !empty($subtask_row['parent_id'])) {
            $attributes['task_id'] = $subtask_row['parent_id'];
        } elseif (array_key_exists('task_id', $attributes)) {
            unset($attributes['task_id']);
        }

        foreach (['name', 'body', 'specify', 'label_id', 'priority'] as $field) {
            if (array_key_exists($field, $attributes)) {
                unset($attributes[$field]);
            }
        }

        $subtask_row['raw_additional_properties'] = count($attributes) ? serialize($attributes) : null;
    }
}
