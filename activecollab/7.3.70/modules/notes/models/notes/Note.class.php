<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\History\Renderers\IsHiddenFromClientsHistoryFieldRenderer;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\Notes\Events\DataObjectLifeCycleEvents\NoteCreatedEvent;
use ActiveCollab\Module\Notes\Events\DataObjectLifeCycleEvents\NoteMoveToTrashEvent;
use Angie\Search\SearchDocument\SearchDocumentInterface;

final class Note extends BaseNote implements RoutingContextInterface
{
    use RoutingContextImplementation;

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'note_group_id',
            ]
        );
    }

    /**
     * Return array of note versions.
     *
     * @return array
     */
    public function getVersions()
    {
        return AngieApplication::cache()->getByObject($this, 'versions', function () {
            $versions = [];

            if ($rows = DB::execute("SELECT l.id, l.created_on, l.created_by_id, l.created_by_name, l.created_by_email, lv.field, lv.old_value, lv.new_value FROM modification_logs AS l LEFT JOIN modification_log_values AS lv ON l.id = lv.modification_id WHERE l.parent_type = 'Note' AND l.parent_id = ? AND lv.field IN ('name', 'body') ORDER BY id", $this->getId())) {
                $rows->setCasting(['created_on' => DBResult::CAST_DATETIME]);

                $first_name_value = false;
                $first_body_value = false;

                foreach ($rows as $row) {
                    if (empty($versions[$row['id']])) {
                        $versions[$row['id']] = [
                            'created_on' => $row['created_on'],
                            'created_by_id' => $row['created_by_id'],
                            'created_by_name' => $row['created_by_name'],
                            'created_by_email' => $row['created_by_email'],
                            'name' => false,
                            'body' => false,
                        ];
                    }

                    if ($row['field'] == 'name') {
                        $versions[$row['id']]['name'] = unserialize($row['new_value']);

                        if ($first_name_value === false) {
                            $first_name_value = unserialize($row['old_value']);
                        }
                    } elseif ($row['field'] == 'body') {
                        $versions[$row['id']]['body'] = unserialize($row['new_value']);

                        if ($first_body_value === false) {
                            $first_body_value = unserialize($row['old_value']);
                        }
                    }
                }

                $versions[0] = [
                    'created_on' => $this->getCreatedOn(),
                    'created_by_id' => $this->getCreatedById(),
                    'created_by_name' => $this->getCreatedByName(),
                    'created_by_email' => $this->getCreatedByEmail(),
                    'name' => $first_name_value === false ? $this->getName() : $first_name_value,
                    'body' => $first_body_value === false ? $this->getBody() : $first_body_value,
                    'modification_id' => 0,
                ];

                ksort($versions);

                // ---------------------------------------------------
                //  Fill in the gaps
                // ---------------------------------------------------

                $current_name = $versions[0]['name'];
                $current_body = $versions[0]['body'];

                foreach ($versions as $k => $v) {
                    if ($v['name'] === false) {
                        $versions[$k]['name'] = $current_name;
                    } else {
                        $current_name = $v['name'];
                    }

                    if ($v['body'] === false) {
                        $versions[$k]['body'] = $current_body;
                    } else {
                        $current_body = $v['body'];
                    }
                }

                // ---------------------------------------------------
                //  Reindex version
                // ---------------------------------------------------

                $tmp = [];
                $iteration = 1;

                foreach ($versions as $modification_id => $version) {
                    $version['modification_id'] = $modification_id;

                    $tmp[$iteration++] = $version;
                }

                $versions = $tmp;
            } else {
                $versions[1] = [
                    'created_on' => $this->getCreatedOn(),
                    'created_by_id' => $this->getCreatedById(),
                    'created_by_name' => $this->getCreatedByName(),
                    'created_by_email' => $this->getCreatedByEmail(),
                    'name' => $this->getName(),
                    'body' => $this->getBody(),
                    'modification_id' => 0,
                ];
            }

            return $versions;
        });
    }

    /**
     * Serialize to JSON.
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'note_group_id' => $this->getNoteGroupId(),
                'in_group' => $this->inGroup(),
                'position' => $this->getPosition(),
                'contributor_ids' => $this->getContributorIds(),
            ]
        );
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new ProjectElementSearchDocument($this);
    }

    /**
     * Returns true if this note is in group.
     *
     * @return bool
     */
    public function inGroup()
    {
        return $this->getNoteGroupId() > 0;
    }

    /**
     * Return ID-s of people who contributed to this note (by altering name or body).
     *
     * @return int[]
     */
    public function getContributorIds()
    {
        return AngieApplication::cache()->getByObject($this, 'contributor_ids', function () {
            return Notes::bulkGetContributorIds([['id' => $this->getId(), 'created_by_id' => $this->getCreatedById()]])[$this->getId()];
        });
    }

    public function getHistoryFieldRenderers(): array
    {
        $renderers = parent::getHistoryFieldRenderers();

        $renderers['is_hidden_from_clients'] = new IsHiddenFromClientsHistoryFieldRenderer();

        return $renderers;
    }

    /**
     * Move to trash.
     *
     * @param bool $bulk
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move Note to trash @ ' . __CLASS__);

            Notifications::deleteByParent($this);
            DataObjectPool::announce(new NoteMoveToTrashEvent($this));
            parent::moveToTrash($by, $bulk);

            $note_group = $this->getNoteGroup();

            if ($note_group instanceof NoteGroup) {
                AngieApplication::cache()->removeByObject($note_group);
            }

            DB::commit('Done: move Note to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move Note to trash @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Get note group.
     *
     * @return NoteGroup|DataObject
     */
    public function &getNoteGroup()
    {
        return DataObjectPool::get(NoteGroup::class, $this->getNoteGroupId());
    }

    /**
     * Restore from trash.
     *
     * @param bool $bulk
     */
    public function restoreFromTrash($bulk = false)
    {
        parent::restoreFromTrash($bulk);

        $note_group = $this->getNoteGroup();

        if ($note_group instanceof NoteGroup) {
            AngieApplication::cache()->removeByObject($note_group);
        }
    }

    // ---------------------------------------------------
    //  Trash
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
        $old_note_group = $this->getNoteGroup();

        // ---------------------------------------------------
        //  Move note from group to project level
        // ---------------------------------------------------

        if ($project->getId() == $this->getProjectId()) {
            if ($this->inGroup()) {
                try {
                    DB::beginWork('Begin: move note from group to project level @ ' . __CLASS__);

                    Notes::pushPositionsInProject($project, $by);

                    $this->setPosition(0);
                    $this->setNoteGroupId(0);
                    $this->save();

                    DB::commit('Done: move note from group to project level @ ' . __CLASS__);
                } catch (Exception $e) {
                    DB::rollback('Rollback: move note from group to project level @ ' . __CLASS__);

                    throw $e;
                }
            }

            // ---------------------------------------------------
            //  Move to a different project
            // ---------------------------------------------------
        } else {
            try {
                DB::beginWork('Begin: move notes to project @ ' . __CLASS__);

                if (!$this->inGroup()) {
                    Notes::pushPositionsInProject($project, $by);
                    $this->setPosition(0);
                }

                parent::moveToProject($project, $by, $before_save, $after_save);

                DataObjectPool::announce(new NoteCreatedEvent($this));

                if ($this->inGroup()) {
                    $this->setNoteGroupId(0);
                    $this->save();
                }

                DB::commit('Done: move note to project @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Rollback: move note to project @ ' . __CLASS__);

                throw $e;
            }
        }

        if ($old_note_group instanceof NoteGroup) {
            AngieApplication::cache()->removeByObject($old_note_group);
        }
    }

    /**
     * Return true if $user can move this element to $target_project.
     *
     * @return bool
     */
    public function canMoveToProject(User $user, Project $target_project)
    {
        // ---------------------------------------------------
        //  If we are moving to the same project, we need to
        //  do a bit differnet permissions check
        // ---------------------------------------------------

        if ($this->getProjectId() == $target_project->getId()) {
            return $this->inGroup() ? $this->canEdit($user) : false; // Only subnotes can be moved to the same project

            // ---------------------------------------------------
            //  Moving to another project? Use regular rules
            // ---------------------------------------------------
        } else {
            $can_move = parent::canMoveToProject($user, $target_project);

            if ($user->isPowerClient(true)) {
                return $can_move && $this->isCreatedBy($user);
            } elseif ($user->isClient()) {
                return false;
            } else {
                return $can_move;
            }
        }
    }

    public function canCopyToProject(User $user, Project $target_project)
    {
        $can_copy = parent::canCopyToProject($user, $target_project);

        if ($user->isPowerClient(true)) {
            return $can_copy && $this->isCreatedBy($user);
        } elseif ($user->isClient()) {
            return false;
        } else {
            return $can_copy;
        }
    }

    // ---------------------------------------------------
    //  Move and Copy
    // ---------------------------------------------------

    public function canEdit(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Return true if user can move note to group.
     *
     * @return bool
     */
    public function canMoveToGroup(NoteGroup $target_group, User $user)
    {
        return !$user instanceof Client && $this->canEdit($user) && $this->getNoteGroupId() != $target_group->getId();
    }

    /**
     * Move note to group.
     *
     * @param  bool $as_first_in_group
     * @return Note
     */
    public function moveToGroup(NoteGroup $target_group, $as_first_in_group = false)
    {
        try {
            DB::beginWork('Begin: move note to group @ ' . __CLASS__);

            $old_note_group = $this->getNoteGroup();
            $note_position = $as_first_in_group ? 1 : Notes::getNextNotePosition($target_group);

            $this->setNoteGroup($target_group);
            $this->setPosition($note_position);
            $this->save();

            if ($as_first_in_group) {
                DB::execute('UPDATE notes SET position = position + 1 WHERE id != ? AND note_group_id = ?', $this->getId(), $target_group->getId());
                Notes::clearCache();
            }

            if ($old_note_group instanceof NoteGroup && !$old_note_group->countNotes()) {
                $old_note_group->delete();
            }

            DB::commit('Done: note moved to group @ ' . __CLASS__);

            AngieApplication::cache()->removeByObject($target_group);

            return $this;
        } catch (Exception $e) {
            DB::rollback('Rollback: move note to group @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Set note group.
     */
    public function setNoteGroup(NoteGroup $note_group)
    {
        $this->setNoteGroupId($note_group->getId());
    }

    public function copyToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    ): IProjectElement
    {
        try {
            DB::beginWork('Begin: move note to project @ ' . __CLASS__);

            Notes::pushPositionsInProject($project, $by);

            $note_copy = parent::copyToProject(
                $project,
                $by,
                function (Note &$c) use ($before_save) {
                    $c->setPosition(0);
                    $c->setNoteGroupId(0);

                    if ($before_save) {
                        $before_save($c);
                    }
                },
                $after_save
            );

            DB::commit('Done: move note to project @ ' . __CLASS__);

            return $note_copy;
        } catch (Exception $e) {
            DB::rollback('Rollback: move note to project @ ' . __CLASS__);

            throw $e;
        }
    }

    // ---------------------------------------------------
    //  Interface implementations
    // ---------------------------------------------------

    public function getRoutingContext(): string
    {
        return 'note';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getProjectId(),
            'note_id' => $this->getId(),
        ];
    }

    /**
     * Delete application object from database.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: delete note @ ' . __CLASS__);

            $note_group = $this->getNoteGroup();

            parent::delete($bulk);

            if ($note_group instanceof NoteGroup && !$note_group->countNotes()) {
                $note_group->delete();
            }

            DB::commit('Done: note deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: note delete @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors &$errors)
    {
        $this->validatePresenceOf('name') or $errors->addError('Name is required', 'name');

        parent::validate($errors);
    }

    /**
     * Include plain text version of body in the JSON response.
     *
     * @return bool
     */
    protected function includePlainTextBodyInJson()
    {
        return true;
    }

    /**
     * Return which modifications should we remember.
     *
     * @return array
     */
    protected function whatIsWorthRemembering()
    {
        return Notes::whatIsWorthRemembering();
    }
}
