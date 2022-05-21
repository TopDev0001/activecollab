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

class NotesController extends ProjectController
{
    use MoveToProjectControllerAction;

    /**
     * Selected note.
     *
     * @var Note
     */
    protected $active_note;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        if ($this->active_project->isNew() || !$this->active_project->canView($user)) {
            return Response::NOT_FOUND;
        }

        $this->active_note = DataObjectPool::get('Note', $request->getId('note_id'));

        if (empty($this->active_note)) {
            $this->active_note = new Note();
            $this->active_note->setProject($this->active_project);
        }

        if ($this->active_note->getProjectId() != $this->active_project->getId()) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * Return project notes.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        AccessLogs::logAccess($this->active_project, $user);

        $collection_name = $user instanceof Client ? 'public_notes_in_project_' . $this->active_project->getId() : 'all_notes_in_project_' . $this->active_project->getId();

        return Notes::prepareCollection($collection_name, $user);
    }

    /**
     * Reorder pages.
     *
     * @return int
     */
    public function reorder(Request $request, User $user)
    {
        if (Notes::canReorder($user, $this->active_project)) {
            $note_ids = $request->put();

            $notes = $note_ids && is_foreachable($note_ids) ? Notes::findByIds($note_ids, true) : null;

            if (empty($notes)) {
                return Response::BAD_REQUEST;
            }

            Notes::reorder($this->active_project, $notes);

            return $note_ids;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Show single note.
     *
     * @return int|Note
     */
    public function view(Request $request, User $user)
    {
        return $this->active_note->isLoaded() && $this->active_note->canView($user) ? AccessLogs::logAccess($this->active_note, $user) : Response::NOT_FOUND;
    }

    /**
     * @return array|int
     */
    public function versions(Request $request, User $user)
    {
        return $this->active_note->isLoaded() && $this->active_note->canView($user) ? $this->active_note->getVersions() : Response::NOT_FOUND;
    }

    /**
     * Create a new note.
     *
     * @return Note|int
     */
    public function add(Request $request, User $user)
    {
        if (Notes::canAdd($user, $this->active_project)) {
            $post = $request->post();
            $post['project_id'] = $this->active_project->getId();

            return Notes::create($post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Update existing note.
     *
     * @return Note|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_note->isLoaded() && $this->active_note->canEdit($user) ? Notes::update($this->active_note, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Move select note to trash.
     *
     * @return Note|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_note->isLoaded() && $this->active_note->canDelete($user) ? Notes::scrap($this->active_note) : Response::NOT_FOUND;
    }

    /**
     * Move to group.
     *
     * @return Note|int
     */
    public function move_to_group(Request $request, User $user)
    {
        $first_in_group = (bool) $request->put('first_in_group');
        $note_group = DataObjectPool::get('NoteGroup', $request->put('note_group_id'));

        if (!$note_group instanceof NoteGroup || $note_group->getProjectId() != $this->active_project->getId() || $this->active_note->inGroup()) {
            return Response::BAD_REQUEST;
        }

        return $this->active_note->isLoaded() && $this->active_note->canMoveToGroup($note_group, $user) ? $this->active_note->moveToGroup($note_group, $first_in_group) : Response::NOT_FOUND;
    }

    /**
     * @return Note
     */
    public function &getObjectToBeMoved()
    {
        return $this->active_note;
    }
}
