<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Search\SearchItem\SearchItemInterface;

trait IProjectElementImplementation
{
    public function registerIProjectElementImplementation()
    {
        $this->addHistoryFields('project_id');

        if ($this->fieldExists('is_hidden_from_clients')) {
            $this->addHistoryFields('is_hidden_from_clients');
        }

        $this->registerEventHandler('on_json_serialize', function (array &$result) {
            $result['project_id'] = $this->getProjectId();

            if ($this->fieldExists('is_hidden_from_clients')) {
                $result['is_hidden_from_clients'] = $this->getIsHiddenFromClients();
            }
        });

        $this->registerEventHandler('on_validate', function (ValidationErrors &$errors) {
            if (!$this->validatePresenceOf('project_id')) {
                $errors->addError('Please select a project', 'project_id');
            }
        });

        $this->registerEventHandler('on_after_touch', function ($by, $additional, $save) {
            if ($save && ($project = $this->getProject())) {
                $project->touch($by, $additional, $save);
            }
        });

        $this->registerEventHandler('on_after_save', function ($is_new, $modifications) {
            if ($is_new && ($project = $this->getProject())) {
                $project->touch();
            }

            if (!$is_new && ($this instanceof IComments || $this instanceof IAttachments || $this instanceof IActivityLog) && (isset($modifications['is_hidden_from_clients']) || isset($modifications['project_id']))) {
                $parent_conditions = [DB::prepare('(parent_type = ? AND parent_id = ?)', get_class($this), $this->getId())];

                if ($this instanceof IComments && $comment_ids = DB::executeFirstColumn('SELECT id FROM comments WHERE ' . Comments::parentToCondition($this))) {
                    $parent_conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', 'Comment', $comment_ids);
                }

                /** @var int[] $attachment_ids */
                if ($attachment_ids = DB::executeFirstColumn('SELECT id FROM attachments WHERE ' . implode(' OR ', $parent_conditions))) {
                    $fields_to_update = [];

                    if (isset($modifications['is_hidden_from_clients'])) {
                        $fields_to_update[] = DB::prepare('is_hidden_from_clients = ?', $modifications['is_hidden_from_clients'][1]);
                    }

                    if (isset($modifications['project_id'])) {
                        $fields_to_update[] = DB::prepare('project_id = ?', $modifications['project_id'][1]);
                    }

                    DB::execute('UPDATE attachments SET ' . implode(',', $fields_to_update) . ' WHERE id IN (?)', $attachment_ids);
                    Attachments::clearCacheFor($attachment_ids);
                }

                if ($this instanceof IActivityLog && ($activity_log_ids = DB::executeFirstColumn('SELECT id FROM activity_logs WHERE ' . ActivityLogs::parentToCondition($this)))) {
                    DB::execute('UPDATE activity_logs SET parent_path = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $this->getObjectPath(), $activity_log_ids);
                    ActivityLogs::clearCacheFor($activity_log_ids);
                }
            }
        });

        if ($this instanceof SearchItemInterface) {
            $this->addSearchFields('project_id', 'is_hidden_from_clients');
        }
    }

    abstract public function addHistoryFields(string ...$field_names): void;

    /**
     * Check if specific field is defined.
     *
     * @param  string $field Field name
     * @return bool
     */
    abstract public function fieldExists($field);

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return project ID.
     *
     * @return int
     */
    abstract public function getProjectId();

    // ---------------------------------------------------
    //  Move and Copy
    // ---------------------------------------------------

    /**
     * Return true if this particular project element is hidden from clients.
     *
     * @return bool
     */
    abstract public function getIsHiddenFromClients();

    /**
     * Validates presence of specific field.
     *
     * In case of string value is trimmed and compared with the empty string. In
     * case of any other type empty() function is used. If $min_value argument is
     * provided value will also need to be larger or equal to it
     * (validateMinValueOf validator is used)
     *
     * @param  string  $field     Field name
     * @param  mixed   $min_value
     * @param  Closure $modifier
     * @return bool
     */
    abstract public function validatePresenceOf($field, $min_value = null, $modifier = null);

    /**
     * Return project instance.
     *
     * @return Project
     */
    public function &getProject()
    {
        return DataObjectPool::get(Project::class, $this->getProjectId());
    }

    /**
     * Return object path.
     *
     * @return string
     */
    public function getObjectPath()
    {
        return 'projects/' . $this->getProjectId() . '/' . ($this->getIsHiddenFromClients() ? 'hidden-from-clients' : 'visible-to-clients') . '/' . str_replace('_', '-', $this->getModelName(true)) . '/' . $this->getId();
    }

    abstract public function getModelName(
        bool $underscore = false,
        bool $singular = false
    ): string;

    /**
     * Return notification subject prefix.
     */
    public function getNotificationSubjectPrefix()
    {
        return $this->getProject() instanceof Project ? '[' . $this->getProject()->getName() . '] ' : '';
    }

    /**
     * Return true if $user can move this element to $target_project.
     *
     * @return bool
     */
    public function canMoveToProject(User $user, Project $target_project)
    {
        if ($this->getProjectId() == $target_project->getId()) {
            return false;
        }

        if ($this instanceof ITrash && $this->getIsTrashed()) {
            return false;
        }

        return $user->isOwner() || ($this->canEdit($user) && $target_project->isMember($user));
    }

    /**
     * Return true if $user can create a copy of this element in $target_project.
     *
     * @return bool
     */
    public function canCopyToProject(User $user, Project $target_project)
    {
        if ($this instanceof ITrash && $this->getIsTrashed()) {
            return false;
        }

        return $user->isOwner() || ($this->canEdit($user) && $target_project->isMember($user));
    }

    public function copyToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    ): IProjectElement
    {
        try {
            DB::beginWork('Copy project object to a project @ ' . __CLASS__);

            $copy = $this->copy();

            $copy->setProjectId($project->getId());

            if ($copy instanceof ICreatedOn) {
                $copy->setCreatedOn(new DateTimeValue());
            }

            if ($copy instanceof ICreatedBy) {
                $copy->setCreatedBy($by);
            }

            if ($before_save instanceof Closure) {
                $before_save($copy);
            }

            $copy->save();

            if ($after_save instanceof Closure) {
                $after_save($copy);
            }

            if ($this instanceof ISubscriptions) {
                $this->cloneSubscribersTo($copy, $project->getMemberIds());
            }

            if ($this instanceof IAttachments) {
                $this->cloneAttachmentsTo($copy);
            }

            $this->getProject()->touch();

            if ($project->getId() != $this->getProjectId()) {
                $project->touch();
            }

            DB::commit('Project object copied to a project @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to copy project object to a project @ ' . __CLASS__);

            throw $e;
        }

        return $copy;
    }

    /**
     * Get global reference.
     *
     * @return string
     */
    public function getGlobalReference() {
        if ($this->getType() == Note::class) {
            return $this->getType() . '-' . $this->getId() . '-' . $this->getProjectId() . '-' . $this->getNoteGroupId();
        }

        return $this->getType() . '-' . $this->getId() . '-' . $this->getProjectId();
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Move this project element to project.
     */
    public function moveToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    )
    {
        $current_project_id = $this->getProjectId();
        $target_project_id = $project->getId();

        if ($current_project_id === $target_project_id) {
            return; // already in target $project
        }

        try {
            DB::beginWork('Moving object to project @ ' . __CLASS__);

            $old_project = $this->getProject();
            $this->setProject($project);

            $project_users_ids = $project->getMemberIds();

            // ---------------------------------------------------
            //  Clean up subscribers
            // ---------------------------------------------------

            if ($this instanceof ISubscriptions && $subscribers = $this->getSubscribers()) {
                foreach ($subscribers as $subscriber) {
                    if ($subscriber instanceof User && !in_array($subscriber->getId(), $project_users_ids)) {
                        $this->unsubscribe($subscriber, true);
                    }
                }
            }

            if ($this instanceof IUpdatedBy) {
                $this->setUpdatedBy($by);
            }

            if ($before_save instanceof Closure) {
                $before_save($this);
            }

            $this->save();

            if ($after_save instanceof Closure) {
                $after_save($this);
            }

            $old_project->touch();
            $project->touch();

            DataObjectPool::announce(
                $this,
                DataObjectPool::OBJECT_UPDATED,
                [
                    'moved_to_project' => [
                        'from' => $current_project_id,
                        'to' => $target_project_id,
                    ],
                ]
            );

            DB::commit('Moved to project @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to move to project @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Set parent project.
     */
    public function setProject(Project $project)
    {
        $this->setProjectId($project->getId());
    }

    /**
     * Set value of project_id field.
     *
     * @param  int $value
     * @return int
     */
    abstract public function setProjectId($value);

    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function canEdit(User $user): bool
    {
        return $this->canView($user) && ($this->isCreatedBy($user) || $user->isMember());
    }

    public function canView(User $user): bool
    {
        $project = $this->getProject();

        if ($project instanceof Project && $project->canView($user)) {
            if ($user instanceof Client && $this->getIsHiddenFromClients()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Return true if $user is author of this object.
     *
     * @return bool
     */
    abstract public function isCreatedBy(IUser $user);

    /**
     * Return true if $user can view access logs.
     *
     * @return bool
     */
    public function canViewAccessLogs(User $user)
    {
        return $user->isPowerUser() || $this->getProject()->isLeader($user);
    }

    /**
     * Return which modifications should we remember.
     *
     * @return bool
     */
    protected function whatIsWorthRemembering()
    {
        return call_user_func([$this->getModelName(), 'whatIsWorthRemembering']);
    }

    protected function getSearchEngine()
    {
        return AngieApplication::search();
    }
}
