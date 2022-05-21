<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('project', SystemModule::NAME);

class NoteGroupsController extends ProjectController
{
    /**
     * Selected note group.
     *
     * @var NoteGroup
     */
    protected $active_note_group;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        if ($this->active_project->isNew() || !$this->active_project->canView($user)) {
            return Response::NOT_FOUND;
        }

        $this->active_note_group = DataObjectPool::get('NoteGroup', $request->getId('note_group_id'));

        if (empty($this->active_note_group)) {
            $this->active_note_group = new NoteGroup();
            $this->active_note_group->setProjectId($this->active_project->getId());
        }

        if ($this->active_note_group->getProjectId() != $this->active_project->getId()) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * Return project note groups.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        return NoteGroups::prepareCollection('all_note_groups_in_project_' . $this->active_project->getId(), $user);
    }

    /**
     * Return notes collection.
     *
     * @return ModelCollection|void
     */
    public function notes(Request $request, User $user)
    {
        $collection_name = $user instanceof Client ? 'public_notes_in_collection_' . $this->active_note_group->getId() : 'all_notes_in_collection_' . $this->active_note_group->getId();

        return Notes::prepareCollection($collection_name, $user);
    }

    /**
     * Create new note group.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        if (NoteGroups::canAdd($user, $this->active_project)) {
            $post = $request->post();
            $post['project_id'] = $this->active_project->getId();

            return NoteGroups::create($post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Return note group.
     *
     * @return NoteGroup|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_note_group->isLoaded() && $this->active_note_group->canView($user) ? $this->active_note_group : Response::NOT_FOUND;
    }

    /**
     * Move to another group.
     *
     * @return NoteGroup|int
     */
    public function move_to_group(Request $request, User $user)
    {
        $note_group = DataObjectPool::get(NoteGroup::class, $request->put('note_group_id'));

        if (!$note_group instanceof NoteGroup) {
            return Response::BAD_REQUEST;
        }

        return $this->active_note_group->isLoaded() && $this->active_note_group->canMoveToGroup($note_group, $user) ? $this->active_note_group->moveToGroup($note_group, $user) : Response::NOT_FOUND;
    }

    /**
     * Reorder group notes.
     *
     * @return int
     */
    public function reorder_notes(Request $request, User $user)
    {
        if ($this->active_note_group->canEdit($user)) {
            $note_ids = $request->put();

            $notes = $note_ids && is_foreachable($note_ids) ? Notes::findByIds($note_ids, true) : null;

            if (empty($notes)) {
                return Response::BAD_REQUEST;
            }

            Notes::reorder($this->active_note_group, $notes->toArray());

            return $note_ids;
        }

        return Response::NOT_FOUND;
    }
}
