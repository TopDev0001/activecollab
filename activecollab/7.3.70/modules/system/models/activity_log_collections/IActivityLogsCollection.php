<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait IActivityLogsCollection
{
    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    /**
     * Run the query and return DB result.
     *
     * @return DbResult|DataObject[]
     */
    public function execute()
    {
        foreach (['getWhosAsking', 'getForOrBy'] as $method_name) {
            if (method_exists($this, $method_name)) {
                /** @var User $user */
                if ($user = $this->$method_name()) {
                    $this->preload_user_details[] = $user->getId();
                }
            }
        }

        /** @var ActivityLog[] $activity_logs */
        if ($activity_logs = $this->getActivityLogsCollection()->execute()) {
            $type_ids_map = [];

            foreach ($activity_logs as $activity_log) {
                $this->analyzeForPreload($activity_log);
            }

            $this->preload();

            foreach ($activity_logs as $activity_log) {
                $parent_type = $activity_log->getParentType();

                if (empty($type_ids_map[$parent_type])) {
                    $type_ids_map[$parent_type] = [];
                }

                if (!in_array($activity_log->getParentId(), $type_ids_map[$parent_type])) {
                    $type_ids_map[$parent_type][] = $activity_log->getParentId();
                }

                $activity_log->onRelatedObjectsTypeIdsMap($type_ids_map);
            }

            $related = DataObjectPool::getByTypeIdsMap($type_ids_map);
        } else {
            $activity_logs = [];
            $related = [];
        }

        return ['activity_logs' => $activity_logs, 'related' => $related];
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        return $this->getActivityLogsCollection()->count();
    }

    /**
     * @var array
     */
    protected $preload_user_details = [];

    /**
     * @var array
     */
    private $types_with_attachments = [];
    private $preload_attachment_details = [];

    /**
     * @var array
     */
    private $types_with_comments = [];
    private $preload_comment_details = [];

    /**
     * @var array
     */
    private $types_with_labels = [];
    private $preload_label_details = [];

    /**
     * @var array
     */
    private $types_with_reactions = [];
    private $preload_reaction_details = [];

    /**
     * @var array
     */
    private $preload_subtask_details = [];

    /**
     * @var array
     */
    private $types_with_time_records = [];
    private $preload_time_record_details = [];

    /**
     * @var array
     */
    private $types_with_expenses = [];
    private $preload_expense_details = [];

    /**
     * @var array
     */
    private $task_ids = [];
    private $project_ids = [];

    /**
     * Analyze record for preload().
     */
    protected function analyzeForPreload(Activitylog $activity_log)
    {
        $parent_type = $activity_log->getParentType();
        $parent_id = $activity_log->getParentId();

        if ($activity_log instanceof CommentCreatedActivityLog) {
            if (empty($this->preload_attachment_details['Comment'])) {
                $this->preload_attachment_details['Comment'] = [];
            }
            if (empty($this->preload_reaction_details['Comment'])) {
                $this->preload_reaction_details['Comment'] = [];
            }

            in_array($activity_log->getCommentId(), $this->preload_attachment_details['Comment']) or $this->preload_attachment_details['Comment'][] = $activity_log->getCommentId();
            in_array($activity_log->getCommentId(), $this->preload_reaction_details['Comment']) or $this->preload_reaction_details['Comment'][] = $activity_log->getCommentId();
        }

        if ($activity_log instanceof SubtaskCreatedActivityLog || $activity_log instanceof SubtaskUpdatedActivityLog) {
            if (!in_array($activity_log->getSubtaskId(), $this->preload_subtask_details)) {
                $this->preload_subtask_details[] = $activity_log->getSubtaskId();
            }
        }

        if ($activity_log instanceof InstanceUpdatedActivityLog) {
            $modificaitons = $activity_log->getModifications();

            if (isset($modificaitons['assignee_id'])) {
                if ($modificaitons['assignee_id'][0]) {
                    $this->preload_user_details[] = $modificaitons['assignee_id'][0];
                }

                if ($modificaitons['assignee_id'][1]) {
                    $this->preload_user_details[] = $modificaitons['assignee_id'][1];
                }
            }
        }

        if ($activity_log->getCreatedById()) {
            $this->preload_user_details[] = $activity_log->getCreatedById();
        }

        if ((new ReflectionClass($parent_type))->isSubclassOf('User')) {
            $this->preload_user_details[] = $parent_id;
        }

        // Comments
        if ($this->parentTypeImplementsComments($parent_type)) {
            if (empty($this->preload_comment_details[$parent_type])) {
                $this->preload_comment_details[$parent_type] = [$parent_id];
            } else {
                if (!in_array($parent_id, $this->preload_comment_details[$parent_type])) {
                    $this->preload_comment_details[$parent_type][] = $parent_id;
                }
            }
        }

        // Attachments
        if ($this->parentTypeImplementsAttachments($parent_type)) {
            if (empty($this->preload_attachment_details[$parent_type])) {
                $this->preload_attachment_details[$parent_type] = [$parent_id];
            } else {
                if (!in_array($parent_id, $this->preload_attachment_details[$parent_type])) {
                    $this->preload_attachment_details[$parent_type][] = $parent_id;
                }
            }
        }

        // Labels
        if ($this->parentTypeImplementsLabels($parent_type)) {
            if (empty($this->preload_label_details[$parent_type])) {
                $this->preload_label_details[$parent_type] = [$parent_id];
            } else {
                if (!in_array($parent_id, $this->preload_label_details[$parent_type])) {
                    $this->preload_label_details[$parent_type][] = $parent_id;
                }
            }
        }

        // Reactions
        if ($this->parentTypeImplementsReactions($parent_type)) {
            if (empty($this->preload_reaction_details[$parent_type])) {
                $this->preload_reaction_details[$parent_type] = [$parent_id];
            } else {
                if (!in_array($parent_id, $this->preload_reaction_details[$parent_type])) {
                    $this->preload_reaction_details[$parent_type][] = $parent_id;
                }
            }
        }

        // Time Records and Expensesq
        if ($this->parentTypeImplementsTrackingObject($parent_type)) {
            if ($parent_type === TimeRecord::class) {
                if (!in_array($parent_id, $this->preload_time_record_details)) {
                    $this->preload_time_record_details[] = $parent_id;
                }
            } elseif ($parent_type === Expense::class) {
                if (!in_array($parent_id, $this->preload_expense_details)) {
                    $this->preload_expense_details[] = $parent_id;
                }
            }
        }

        if ($activity_log->getParentType() == Task::class) {
            $this->task_ids[] = $activity_log->getParentId();
        }

        if ($activity_log->getParentType() == Project::class) {
            $this->project_ids[] = $activity_log->getParentId();
        }
    }

    /**
     * Return true if $parent_type implements comments.
     *
     * @param  string $parent_type
     * @return bool
     */
    private function parentTypeImplementsComments($parent_type)
    {
        if (!isset($this->types_with_comments[$parent_type])) {
            $this->types_with_comments[$parent_type] = (new ReflectionClass($parent_type))->implementsInterface('IComments');
        }

        return $this->types_with_comments[$parent_type];
    }

    /**
     * Return true if $parent_type implements attachments.
     *
     * @param  string $parent_type
     * @return bool
     */
    private function parentTypeImplementsAttachments($parent_type)
    {
        if (!isset($this->types_with_attachments[$parent_type])) {
            $this->types_with_attachments[$parent_type] = (new ReflectionClass($parent_type))->implementsInterface('IAttachments');
        }

        return $this->types_with_attachments[$parent_type];
    }

    /**
     * Return true if $parent_type implements labels.
     *
     * @param  string $parent_type
     * @return bool
     */
    private function parentTypeImplementsLabels($parent_type)
    {
        if (!isset($this->types_with_labels[$parent_type])) {
            $this->types_with_labels[$parent_type] = (new ReflectionClass($parent_type))
                ->implementsInterface(ILabels::class);
        }

        return $this->types_with_labels[$parent_type];
    }

    private function parentTypeImplementsReactions($parent_type)
    {
        if (!isset($this->types_with_reactions[$parent_type])) {
            $this->types_with_reactions[$parent_type] = (new ReflectionClass($parent_type))
                ->implementsInterface(IReactions::class);
        }

        return $this->types_with_reactions[$parent_type];
    }

    private function parentTypeImplementsTrackingObject($parent_type)
    {
        if ($parent_type === TimeRecord::class) {
            if (!isset($this->types_with_time_records[$parent_type])) {
                $this->types_with_time_records[$parent_type] = (new ReflectionClass($parent_type))
                    ->implementsInterface(ITrackingObject::class);
            }

            return $this->types_with_time_records[$parent_type];
        } elseif ($parent_type === Expense::class) {
            if (!isset($this->types_with_expenses[$parent_type])) {
                $this->types_with_expenses[$parent_type] = (new ReflectionClass($parent_type))
                    ->implementsInterface(ITrackingObject::class);
            }

            return $this->types_with_expenses[$parent_type];
        }
    }

    /**
     * Preload details when possible.
     */
    protected function preload()
    {
        if (count($this->preload_subtask_details)) {
            Subtasks::preloadDetailsByIds($this->preload_subtask_details);
        }

        foreach ($this->preload_comment_details as $parent_type => $parent_ids) {
            Comments::preloadCountByParents($parent_type, $parent_ids);
        }

        foreach ($this->preload_attachment_details as $parent_type => $parent_ids) {
            Attachments::preloadDetailsByParents($parent_type, $parent_ids);
        }

        foreach ($this->preload_label_details as $parent_type => $parent_ids) {
            Labels::preloadDetailsByParents($parent_type, $parent_ids);
        }

        foreach ($this->preload_reaction_details as $parent_type => $parent_ids) {
            Reactions::preloadDetailsByParents($parent_type, $parent_ids);
        }

        if (count($this->preload_time_record_details)) {
            TimeRecords::preloadDetailsByIds($this->preload_time_record_details);
        }

        if (count($this->preload_expense_details)) {
            Expenses::preloadDetailsByIds($this->preload_expense_details);
        }

        $this->preload_user_details = array_unique($this->preload_user_details);

        Users::preloadLastLoginOn($this->preload_user_details);
        Users::preloadAdditionalEmailAddresses($this->preload_user_details);

        if (count($this->task_ids)) {
            $this->task_ids = array_unique($this->task_ids);

            Subtasks::preloadCountByTasks($this->task_ids);
            TaskDependencies::preloadCountByTasks($this->task_ids);

            DataObjectPool::getByIds(Task::class, $this->task_ids);
        }

        if (count($this->project_ids)) {
            $this->project_ids = array_unique($this->project_ids);

            if (count($this->project_ids) > 1) {
                Projects::preloadProjectElementCounts($this->project_ids);
            }
        }
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * @return string
     */
    abstract public function getTimestampHash();

    /**
     * Return assigned tasks collection.
     *
     * @return ModelCollection
     * @throws ImpossibleCollectionError
     */
    abstract protected function &getActivityLogsCollection();

    /**
     * Prepare collection tag from bits of information.
     *
     * @param  string $user_email
     * @param  string $hash
     * @return string
     */
    abstract protected function prepareTagFromBits($user_email, $hash);
}
