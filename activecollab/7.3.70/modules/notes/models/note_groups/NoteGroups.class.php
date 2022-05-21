<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * NoteGroups class.
 *
 * @package ActiveCollab.modules.notes
 * @subpackage models
 */
class NoteGroups extends BaseNoteGroups
{
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

        $bits = explode('_', $collection_name);
        $project_id = array_pop($bits);

        if (str_starts_with($collection_name, 'all_note_groups_in_project')) {
            $project = DataObjectPool::get('Project', $project_id);

            if ($project instanceof Project) {
                $collection->setConditions('project_id = ?', $project->getId());
            } else {
                throw new ImpossibleCollectionError('Project not found');
            }
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    /**
     * Can add.
     *
     * @return bool
     */
    public static function canAdd(User $user, Project $project)
    {
        return !$user instanceof Client && ($user->isOwner() || $project->isMember($user));
    }

    /**
     * Reorder note groups.
     *
     * @param NoteGroup[] $note_groups
     */
    public static function reorder(Project $project, $note_groups)
    {
        DB::transact(function () use ($project, $note_groups) {
            $counter = 1;

            foreach ($note_groups as $note_group) {
                if ($note_group->getProjectId() == $project->getId()) {
                    $note_group->setPosition($counter++);
                    $note_group->save();
                }
            }
        }, 'Reordering note groups');

        self::clearCache();
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): NoteGroup
    {
        $user = AngieApplication::authentication()->getLoggedUser();

        if (isset($attributes['project_id']) && $attributes['project_id']) {
            $attributes['position'] = DB::executeFirstCell('SELECT MAX(position) FROM note_groups WHERE project_id = ?', $attributes['project_id']) + 1;
        }

        try {
            DB::beginWork('Begin: create note group @ ' . __CLASS__);

            $note_group = parent::create($attributes, $save, $announce); // @TODO Announcement should be sent after notes have been handled

            if (array_key_exists('note_ids', $attributes) && is_foreachable($attributes['note_ids'])) {
                $notes = Notes::findByIds($attributes['note_ids'], true);

                $position = 1;

                foreach ($notes as $note) {
                    if ($note->getProjectId() == $note_group->getProjectId() && $note->canMoveToGroup($note_group, $user)) {
                        $note->setNoteGroup($note_group);
                        $note->setPosition($position++);
                        $note->save();
                    }
                }
            }

            DB::commit('Done: note group created @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: create note group @ ' . __CLASS__);

            throw $e;
        }

        return $note_group;
    }
}
