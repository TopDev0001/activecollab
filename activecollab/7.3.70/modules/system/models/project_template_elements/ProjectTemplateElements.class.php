<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTemplateElements extends BaseProjectTemplateElements
{
    /**
     * @var bool
     */
    private static $preloaded_counts = false;

    /**
     * Return new collection.
     *
     * @param  User|null         $user
     * @return ModelCollection
     * @throws InvalidParamError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'elements_in_template')) {
            $bits = explode('_', $collection_name);
            $template = DataObjectPool::get('ProjectTemplate', array_pop($bits));
            if ($template instanceof ProjectTemplate) {
                $collection->setConditions('template_id = ?', $template->getId());

                return $collection;
            }
        } elseif (str_starts_with($collection_name, 'project_template_task_suggestion')) {
            return self::prepareTaskSuggestionCollection($collection, $collection_name);
        } elseif (str_starts_with($collection_name, 'parents_for')) {
            return self::prepareParentsCollection($collection, $collection_name);
        } elseif (str_starts_with($collection_name, 'children_for')) {
            return self::prepareChildrenCollection($collection, $collection_name);
        }

        throw new InvalidParamError('collection_name', $collection_name);
    }

    private static function prepareParentsCollection(ModelCollection $collection, string $collection_name)
    {
        $bits = explode('_', $collection_name);

        /** @var ProjectTemplateTask $task */
        if ($task = DataObjectPool::get(ProjectTemplateTask::class, array_pop($bits))) {
            $collection->setConditions(
                'id IN (SELECT td.parent_id FROM project_template_task_dependencies td WHERE td.child_id = ?)',
                $task->getId()
            );

            return $collection;
        }

        throw new InvalidParamError('collection_name', $collection_name, 'ProjectTemplateTask ID expected in collection name');
    }

    private static function prepareChildrenCollection(ModelCollection $collection, string $collection_name)
    {
        $bits = explode('_', $collection_name);

        /** @var ProjectTemplateTask $task */
        if ($task = DataObjectPool::get(ProjectTemplateTask::class, array_pop($bits))) {
            $collection->setConditions(
                'id IN (SELECT td.child_id FROM project_template_task_dependencies td WHERE td.parent_id = ?)',
                $task->getId()
            );

            return $collection;
        }

        throw new InvalidParamError('collection_name', $collection_name, 'ProjectTemplateTask ID expected in collection name');
    }

    private static function prepareTaskSuggestionCollection(ModelCollection $collection, $collection_name)
    {
        $bits = explode('_', $collection_name);

        /** @var ProjectTemplateTask $task */
        if ($task = DataObjectPool::get(ProjectTemplateTask::class, array_pop($bits))) {
            $suggestion_task_ids = DB::executeFirstColumn(
                'SELECT te.id
                    FROM project_template_elements te
                    WHERE te.id != ? AND te.type = ? AND te.template_id = ? AND te.id NOT IN
                    (
                      SELECT td1.child_id
                      FROM project_template_task_dependencies td1
                      WHERE td1.parent_id = ?
                    ) AND te.id NOT IN
                    (
                      SELECT td2.parent_id
                      FROM project_template_task_dependencies td2
                      WHERE td2.child_id = ?
                    )',
                $task->getId(),
                ProjectTemplateTask::class,
                $task->getTemplateId(),
                $task->getId(),
                $task->getId()
            );
            $collection->setConditions('id IN (?)', $suggestion_task_ids);

            return $collection;
        }

        throw new InvalidParamError('collection_name', $collection_name, 'ProjectTemplateTask ID expected in collection name');
    }

    /**
     * Preload counts for the given project templates (to bring the number of queries down).
     *
     * @param bool $force_refresh
     */
    public static function preloadCountByProjectTemplate($force_refresh = false)
    {
        if (self::$preloaded_counts === false || $force_refresh) {
            self::$preloaded_counts = [];

            if ($rows = DB::execute("SELECT type, template_id, COUNT('id') AS 'row_count' FROM project_template_elements WHERE template_id IN (SELECT id FROM project_templates WHERE is_trashed = ?) GROUP BY template_id, type", false)) {
                foreach ($rows as $row) {
                    if (!isset(self::$preloaded_counts[$row['template_id']])) {
                        self::$preloaded_counts[$row['template_id']] = [];
                    }

                    self::$preloaded_counts[$row['template_id']][$row['type']] = (int) $row['row_count'];
                }
            }
        }
    }

    /**
     * Return number of elements in $project_template.
     *
     * @param  null     $type
     * @return int|null
     */
    public static function countByProjectTemplate(ProjectTemplate $project_template, $type = null)
    {
        if (self::$preloaded_counts !== false) {
            return isset(self::$preloaded_counts[$project_template->getId()]) && isset(self::$preloaded_counts[$project_template->getId()][$type]) ? self::$preloaded_counts[$project_template->getId()][$type] : 0;
        } else {
            return static::count(['template_id = ? AND type = ?', $project_template->getId(), $type]);
        }
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): ProjectTemplateElement
    {
        if (isset($attributes['type']) && in_array($attributes['type'], self::getAvailableElementClasses())) {
            $uploaded_file_code = array_var($attributes, 'uploaded_file_code', null, true);

            $base_class_properties = self::getFields();

            $base_class_properties[] = 'attach_uploaded_files';
            $base_class_properties[] = 'drop_attached_files';

            if (
                $attributes['type'] === ProjectTemplateRecurringTask::class
                || $attributes['type'] === ProjectTemplateTask::class
                || $attributes['type'] === ProjectTemplateTaskList::class
            ) {
                $base_class_properties[] = 'labels';

                if (isset($attributes['template_id']) && empty($attributes['position'])) {
                    $attributes['position'] = DB::executeFirstCell(
                        'SELECT MAX(position) FROM project_template_elements WHERE type = ? AND template_id = ?',
                        $attributes['type'],
                        $attributes['template_id']
                    ) + 1;
                }
            }

            $all_other_properties = [];

            // For recurring task edge case
            if (
                $attributes['type'] == ProjectTemplateRecurringTask::class
                && isset($attributes['attachments'])
                && !empty($attributes['attachments'])
            ) {
                $attachments_ids = [];
                /** @var Attachment $attachment */
                foreach ($attributes['attachments'] as $attachment) {
                    $attachments_ids[] = $attachment['id'];
                }
            }

            foreach ($attributes as $k => $v) {
                if (!in_array($k, $base_class_properties)) {
                    $all_other_properties[$k] = $v;
                    unset($attributes[$k]);
                }
            }

            $uploaded_file = null;
            if ($attributes['type'] == ProjectTemplateFile::class && $uploaded_file_code) {
                $uploaded_file = UploadedFiles::findByCode($uploaded_file_code);

                if ($uploaded_file instanceof UploadedFile) {
                    $attributes['name'] = $uploaded_file->getName();
                    $all_other_properties['type'] = str_replace('UploadedFile', '', get_class($uploaded_file)) . 'File';
                    $all_other_properties['mime_type'] = $uploaded_file->getMimeType();
                    $all_other_properties['size'] = $uploaded_file->getSize();
                    $all_other_properties['location'] = $uploaded_file->getLocation();
                    $all_other_properties['md5'] = $uploaded_file->getMd5();
                    $all_other_properties = array_merge($all_other_properties, $uploaded_file->getAdditionalProperties());
                } else {
                    throw new InvalidParamError('attributes[uploaded_file_code]', $uploaded_file_code);
                }
            }

            $instance = parent::create($attributes, false, false);

            $additional_properties = [];

            foreach ($instance->getElementProperties() as $property => $cast) {
                if (empty($all_other_properties[$property])) {
                    switch ($cast) {
                        case 'array':
                            $additional_properties[$property] = [];
                            break;
                        case 'trim':
                            $additional_properties[$property] = '';
                            break;
                        default:
                            $additional_properties[$property] = call_user_func($cast, null);
                    }

                    continue;
                }

                switch ($cast) {
                    case 'array':
                        $additional_properties[$property] = (array) $all_other_properties[$property];
                        break;
                    case 'trim':
                        $additional_properties[$property] = trim((string) $all_other_properties[$property]);
                        break;
                    default:
                        $additional_properties[$property] = call_user_func($cast, $all_other_properties[$property]);
                }
            }

            if (!self::validateEstimateAndJobType($additional_properties)) {
                throw new ValidationErrors(
                    [
                        'estimate',
                        'job_type_id',
                    ],
                    'Task estimate feature is not enabled in system settings'
                );
            }

            $instance->setAdditionalProperties($additional_properties);

            if ($save) {
                $instance->save();
                if (
                    $attributes['type'] == ProjectTemplateFile::class
                    && $uploaded_file instanceof UploadedFile
                    && $instance instanceof ProjectTemplateFile
                ) {
                    $uploaded_file->keepFileOnDelete(true);
                    $uploaded_file->delete();
                }
            }

            // For recurring task edge case
            if (!empty($attachments_ids)) {
                $attachments = Attachments::findByIds($attachments_ids);
                foreach ($attachments as $attachment) {
                    if ($attachment instanceof GoogleDriveAttachment || $attachment instanceof DropboxAttachment) {
                        $instance->attachExternalFile($attachment, AngieApplication::authentication()->getLoggedUser());
                    } elseif ($attachment instanceof WarehouseAttachment) {
                        $instance->attachWarehouseFile($attachment, AngieApplication::authentication()->getLoggedUser());
                    } else {
                        $instance->attachFile(
                            $attachment->getPath(),
                            $attachment->getName(),
                            $attachment->getMimeType(),
                            AngieApplication::authentication()->getLoggedUser()
                        );
                    }
                }
            }

            if (!empty($additional_properties['is_hidden_from_clients']) && $instance->countAttachments() > 0) {
                $instance->hideOrShowAttachmentsFromClients($additional_properties['is_hidden_from_clients']);
            }

            return $instance;
        } else {
            throw new InvalidParamError(
                'attributes[type]',
                $attributes['type'],
                'Value of "type" field is not a valid project template element class'
            );
        }
    }

    /**
     * Return a list of available sub-classes.
     *
     * @return array
     */
    public static function getAvailableElementClasses()
    {
        return [
            ProjectTemplateTaskList::class,
            ProjectTemplateRecurringTask::class,
            ProjectTemplateTask::class,
            ProjectTemplateSubtask::class,
            ProjectTemplateDiscussion::class,
            ProjectTemplateNoteGroup::class,
            ProjectTemplateNote::class,
            ProjectTemplateFile::class,
        ];
    }

    public static function &update(
        DataObject & $instance,
        array $attributes,
        bool $save = true
    ) : ProjectTemplateElement
    {
        $is_hidden = $instance->getAdditionalProperty('is_hidden_from_clients');
        $attachment_num = $instance->countAttachments();

        $uploaded_file_code = array_var($attributes, 'uploaded_file_code', null, true);

        $base_class_properties = self::getFields();
        $base_class_properties[] = 'attach_uploaded_files';
        $base_class_properties[] = 'drop_attached_files';

        if ($instance instanceof ProjectTemplateTask || $instance instanceof ProjectTemplateRecurringTask) {
            $base_class_properties[] = 'labels';
        }

        $all_other_properties = [];

        foreach ($attributes as $k => $v) {
            if (!in_array($k, $base_class_properties)) {
                $all_other_properties[$k] = $v;
                unset($attributes[$k]);
            }
        }

        if ($instance instanceof ProjectTemplateFile && $uploaded_file_code) {
            $uploaded_file = UploadedFiles::findByCode($uploaded_file_code);

            if ($uploaded_file instanceof UploadedFile) {
                $attributes['name'] = $uploaded_file->getName();
                $all_other_properties['type'] = str_replace('UploadedFile', '', get_class($uploaded_file)) . 'File';
                $all_other_properties['mime_type'] = $uploaded_file->getMimeType();
                $all_other_properties['size'] = $uploaded_file->getSize();
                $all_other_properties['location'] = $uploaded_file->getLocation();
                $all_other_properties['md5'] = $uploaded_file->getMd5();
                if ($uploaded_file->getShareHash()) {
                    $all_other_properties['share_hash'] = $uploaded_file->getShareHash();
                }
            } else {
                throw new InvalidParamError('attributes[uploaded_file_code]', $uploaded_file_code);
            }
        }

        parent::update($instance, $attributes, false);

        $additional_properties = [];

        foreach ($instance->getElementProperties() as $property => $cast) {
            if ($cast === 'array') {
                $additional_properties[$property] = empty($all_other_properties[$property]) ? [] : (array) $all_other_properties[$property];
            } else {
                $additional_properties[$property] = array_key_exists($property, $all_other_properties) ? call_user_func($cast, $all_other_properties[$property]) : call_user_func($cast, null);
            }
        }

        $instance->setAdditionalProperties($additional_properties);

        if ($save) {
            $instance->save();
        }

        $is_added_attachment = $instance->countAttachments() > $attachment_num;

        if (array_key_exists('is_hidden_from_clients', $additional_properties)
            && ($additional_properties['is_hidden_from_clients'] != $is_hidden || $is_added_attachment)
        ) {
            $instance->hideOrShowAttachmentsFromClients($additional_properties['is_hidden_from_clients']);
        }

        return $instance;
    }

    /**
     * Reorder project template elements.
     *
     * @param int[] $elements
     */
    public static function reorder(ProjectTemplate $template, $elements)
    {
        $element_ids = [];

        if ($elements && is_foreachable($elements)) {
            DB::transact(function () use ($template, $elements, &$element_ids) {
                $counter = 1;
                $template_id = DB::escape($template->getId());

                foreach ($elements as $element) {
                    $element_id = $element instanceof ProjectTemplateElement ? $element->getId() : $element;

                    DB::execute("UPDATE project_template_elements SET position = ?, updated_on = UTC_TIMESTAMP() WHERE template_id = $template_id AND id = ?", $counter++, $element_id);

                    $element_ids[] = $element_id;
                }
            }, 'Reordering template elements');
        }

        self::clearCacheFor($element_ids);
    }

    /**
     * Revoke assigns members from users project template elements.
     *
     * @param array|int $users
     * @param int       $template_id
     */
    public static function revokeAssignee($users, $template_id = null)
    {
        $conditions = ['(type = ? OR type = ? OR type = ?)', 'ProjectTemplateTask', 'ProjectTemplateRecurringTask', 'ProjectTemplateSubtask'];
        if (!empty($template_id)) {
            $conditions[0] .= ' AND template_id = ? ';
            $conditions[] = $template_id;
        }

        $users = (array) $users;

        $elements = self::find(['condition' => $conditions]);
        if ($elements) {
            foreach ($elements as $element) {
                $attributes = unserialize($element->getFieldValue('raw_additional_properties'));

                if (!empty($attributes['assignee_id']) && in_array($attributes['assignee_id'], $users)) {
                    $attributes['assignee_id'] = 0;
                }

                if (isset($attributes['subtasks'])) {
                    foreach ($attributes['subtasks'] as $index => $recurring_subtask) {
                        if (in_array($recurring_subtask['assignee_id'], $users)) {
                            $attributes['subtasks'][$index]['assignee_id'] = 0;
                        }
                    }
                }

                $element->setFieldValue('raw_additional_properties', serialize($attributes));
                $element->save();
            }
        }
    }

    private static function validateEstimateAndJobType(array $additional_properties)
    {
        $task_estimates_enabled = ConfigOptions::getValue('task_estimates_enabled');
        if ((array_key_exists('estimate', $additional_properties) || array_key_exists('job_type_id', $additional_properties))) {
            if (((int) $additional_properties['estimate'] > 0 || (int) $additional_properties['job_type_id'] > 0) && !$task_estimates_enabled) {
                return false;
            }
        }

        return true;
    }
}
