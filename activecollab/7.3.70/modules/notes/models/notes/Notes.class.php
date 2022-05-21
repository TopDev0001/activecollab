<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Notes\Events\DataObjectLifeCycleEvents\NoteCreatedEvent;
use ActiveCollab\Module\Notes\Events\DataObjectLifeCycleEvents\NoteUpdatedEvent;

/**
 * Notes manager class.
 *
 * @package activeCollab.modules.notes
 * @subpackage models
 */
class Notes extends BaseNotes
{
    use IProjectElementsImplementation;

    /**
     * Prepare collection.
     *
     * @param  User|null                 $user
     * @return ModelCollection
     * @throws InvalidParamError
     * @throws ImpossibleCollectionError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'public_notes_in_project') || str_starts_with($collection_name, 'all_notes_in_project')) {
            $bits = explode('_', $collection_name);
            $project = DataObjectPool::get('Project', array_pop($bits));

            if ($project instanceof Project) {
                if (str_starts_with($collection_name, 'all_notes_in_project') && !($user instanceof Client)) {
                    $collection->setConditions('project_id = ? AND is_trashed = ?', $project->getId(), false);
                } else {
                    $collection->setConditions('project_id = ? AND is_trashed = ? AND is_hidden_from_clients = ?', $project->getId(), false, false);
                }
            } else {
                throw new ImpossibleCollectionError('Project not found');
            }
        } elseif (str_starts_with($collection_name, 'public_notes_in_collection') || str_starts_with($collection_name, 'all_notes_in_collection')) {
            $bits = explode('_', $collection_name);
            $note_group = DataObjectPool::get('NoteGroup', array_pop($bits));

            if ($note_group instanceof NoteGroup) {
                if (str_starts_with($collection_name, 'all_notes_in_collection') && !($user instanceof Client)) {
                    $collection->setConditions('note_group_id = ? AND is_trashed = ?', $note_group->getId(), false);
                    $collection->setOrderBy('position');
                } else {
                    $collection->setConditions('note_group_id = ? AND is_hidden_from_clients = ? AND is_trashed = ?', $note_group->getId(), false, false);
                }
            } else {
                throw new ImpossibleCollectionError('Note group not found');
            }
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    /**
     * Return true if $user can add notes (in the given project).
     *
     * @return bool
     */
    public static function canAdd(User $user, Project $project)
    {
        return $user instanceof User && ($user->isOwner() || $project->isMember($user));
    }

    /**
     * Returns true if $user can reorder notes in $project.
     *
     * @return bool
     */
    public static function canReorder(IUser $user, Project $project)
    {
        if ($user instanceof User) {
            return $user->isPowerUser() || $project->isLeader($user) || $project->isMember($user);
        }

        return false;
    }

    public static function whatIsWorthRemembering(): array
    {
        return [
            'project_id',
            'name',
            'body',
            'is_trashed',
        ];
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Note
    {
        $note_group = null;

        if (isset($attributes['note_group_id']) && $attributes['note_group_id']) {
            $note_group = DataObjectPool::get(NoteGroup::class, $attributes['note_group_id']);
        }

        if ($note_group instanceof NoteGroup) {
            $attributes['position'] = self::getNextNotePosition($note_group);
        } else {
            $attributes['position'] = 0;

            $project = DataObjectPool::get(Project::class, $attributes['project_id']);

            if ($project instanceof Project) {
                self::pushPositionsInProject($project, AngieApplication::authentication()->getLoggedUser());
            }
        }

        $notify_subscribers = array_var($attributes, 'notify_subscribers', true, true);

        $note = parent::create($attributes, $save, false); // @TODO Announcement should be sent after project leader is subscribed

        if ($note instanceof Note && $note->isLoaded()) {
            /** @var Note $note */
            $note = self::autoSubscribeProjectLeader($note);

            if ($notify_subscribers) {
                AngieApplication::notifications()
                    ->notifyAbout('notes/new_note', $note, $note->getCreatedBy())
                        ->sendToSubscribers();
            }
        }

        if ($note->isLoaded() && $announce) {
            DataObjectPool::announce(new NoteCreatedEvent($note));
        }

        return $note;
    }

    // ---------------------------------------------------
    //  Create and update
    // ---------------------------------------------------

    /**
     * Return next note position for a given parent.
     *
     * @return int
     */
    public static function getNextNotePosition(NoteGroup $note_group)
    {
        return DB::executeFirstCell('SELECT MAX(position) FROM notes WHERE project_id = ? AND note_group_id = ?', $note_group->getProjectId(), $note_group->getId()) + 1;
    }

    /**
     * Push positions in the project to make space for a new note (that goes on top of the list).
     */
    public static function pushPositionsInProject(Project $project, User $user = null)
    {
        DB::execute("UPDATE notes SET position = '0' WHERE project_id = ? AND position IS NULL", $project->getId());

        if ($ids_to_push = DB::executeFirstColumn("SELECT id FROM notes WHERE project_id = ? AND note_group_id = '0' ORDER BY position DESC", $project->getId())) {
            if ($user) {
                foreach ($ids_to_push as $id) {
                    DB::execute(
                        'UPDATE notes SET position = position + 1, updated_on = UTC_TIMESTAMP(), updated_by_id = ?, updated_by_name = ?, updated_by_email = ? WHERE id = ?',
                        $user->getId(),
                        $user->getDisplayName(),
                        $user->getEmail(),
                        $id
                    );
                }
            } else {
                foreach ($ids_to_push as $id) {
                    DB::execute(
                        'UPDATE notes SET position = position + 1, updated_on = UTC_TIMESTAMP() WHERE id = ?',
                        $id
                    );
                }
            }

            self::clearCacheFor($ids_to_push);
        }
    }

    // ---------------------------------------------------
    //  Position and Ordering
    // ---------------------------------------------------

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Note
    {
        if (array_key_exists('note_group_id', $attributes)) {
            unset($attributes['note_group_id']); // Use Note::moveToNoteGroup() instead
        }

        $note = parent::update($instance, $attributes, $save);

        if ($note instanceof Note) {
            DataObjectPool::announce(new NoteUpdatedEvent($note));
        }

        return $note;
    }

    /**
     * Reorder notes.
     *
     * @param  Project|NoteGroup $parent
     * @throws InvalidParamError
     */
    public static function reorder($parent, array $notes)
    {
        DB::transact(function () use ($parent, $notes) {
            $counter = 1;
            $note_group_id = 0;

            if ($parent instanceof Project) {
                $project_id = $parent->getId();
            } elseif ($parent instanceof NoteGroup) {
                $project_id = $parent->getProjectId();
                $note_group_id = $parent->getId();
            } else {
                throw new InvalidInstanceError('parent', $parent, ['Project', 'NoteGroup']);
            }

            foreach ($notes as $note) {
                if ($note->getProjectId() == $project_id && $note->getNoteGroupId() == $note_group_id) {
                    $note->setPosition($counter++);
                    $note->save();
                }
            }
        }, 'Reordering notes in a group');

        self::clearCache();
    }

    /**
     * Bulk return list of contributors for the given notes.
     *
     * @param  array|DbResult $rows
     * @return array
     */
    public static function bulkGetContributorIds($rows)
    {
        $result = [];

        if ($rows && is_foreachable($rows)) {
            foreach ($rows as $row) {
                $result[$row['id']] = [$row['created_by_id']];
            }

            if ($other_contributors = DB::execute('SELECT DISTINCT parent_id AS "note_id", created_by_id FROM modification_logs AS ml LEFT JOIN modification_log_values AS mlv ON ml.id = mlv.modification_id WHERE ml.parent_type = ? AND ml.parent_id IN (?) AND ml.created_by_id > ? AND mlv.field IN (?)', 'Note', array_keys($result), 0, ['name', 'body'])) {
                foreach ($other_contributors as $other_contributor) {
                    if (!in_array($other_contributor['created_by_id'], $result[$other_contributor['note_id']])) {
                        $result[$other_contributor['note_id']][] = $other_contributor['created_by_id'];
                    }
                }
            }
        }

        return $result;
    }
}
