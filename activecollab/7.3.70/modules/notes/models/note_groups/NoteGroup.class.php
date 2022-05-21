<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

final class NoteGroup extends BaseNoteGroup implements RoutingContextInterface
{
    use RoutingContextImplementation;

    public function canView(User $user): bool
    {
        return $user->isOwner() || $this->getProject()->isMember($user);
    }

    /**
     * Return related project.
     *
     * @return Project
     */
    public function &getProject()
    {
        return DataObjectPool::get('Project', $this->getProjectId());
    }

    /**
     * Return true if user can move selected group to target group.
     *
     * @return bool
     */
    public function canMoveToGroup(NoteGroup $target_group, User $user)
    {
        return $this->canEdit($user) && $this->getId() != $target_group->getId();
    }

    public function canEdit(User $user): bool
    {
        return !$user instanceof Client && ($user->isOwner() || $this->getProject()->isMember($user));
    }

    /**
     * Describe object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['project_id'] = $this->getProjectId();

        return $result;
    }

    /**
     * Return numer of notes.
     *
     * @return bool
     */
    public function countNotes()
    {
        if ($this->isLoaded()) {
            return AngieApplication::cache()->getByObject($this, 'notes_count', function () {
                return DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM notes WHERE note_group_id = ? AND project_id = ? AND is_trashed = ?', $this->getId(), $this->getProjectId(), false);
            });
        }

        return 0;
    }

    /**
     * Move notes from selected group into target group.
     *
     * @return NoteGroup
     */
    public function moveToGroup(NoteGroup $target_note_group, User $user)
    {
        if ($notes = $this->getNotes()) {
            $last_note_position = Notes::getNextNotePosition($target_note_group);

            foreach ($notes as $note) {
                if ($note->canMoveToGroup($target_note_group, $user)) {
                    $note->setNoteGroup($target_note_group);
                    $note->setPosition($last_note_position++);
                    $note->save();
                }
            }

            $this->delete();

            Notes::clearCache();
        }

        return $target_note_group;
    }

    /**
     * Get notes.
     *
     * @param  bool  $include_trashed
     * @return array
     */
    public function getNotes($include_trashed = false)
    {
        if ($include_trashed) {
            return Notes::find(['conditions' => ['note_group_id = ? AND project_id = ? AND is_trashed = ?', $this->getId(), $this->getProjectId(), false]]);
        } else {
            return Notes::find(['conditions' => ['note_group_id = ? AND project_id = ?', $this->getId(), $this->getProjectId()]]);
        }
    }

    public function getRoutingContext(): string
    {
        return 'note_group';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getProjectId(),
            'note_group_id' => $this->getId(),
        ];
    }

    /**
     * Validate before save.
     *
     * @param ValidationErrors &$errors
     */
    public function validate(ValidationErrors & $errors)
    {
        if (!$this->validatePresenceOf('project_id')) {
            $errors->addError('Project is required', 'project_id');
        }

        parent::validate($errors);
    }
}
