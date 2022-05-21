<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('selected_object', EnvironmentFramework::INJECT_INTO);

abstract class FwStateController extends SelectedObjectController
{
    /**
     * Selected object.
     *
     * @var ApplicationObject|IArchive|ITrash
     */
    protected $active_object;

    /**
     * Move to archive.
     *
     * @return ApplicationObject|IArchive|int|ITrash
     */
    public function archive(Request $request, User $user)
    {
        if ($this->active_object instanceof IArchive && $this->active_object->canArchive($user)) {
            $this->active_object->moveToArchive($user);

            return $this->active_object;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Restore from archive.
     *
     * @return ApplicationObject|IArchive|int|ITrash
     */
    public function restore_from_archive(Request $request, User $user)
    {
        if ($this->active_object instanceof IArchive && $this->active_object->canArchive($user)) {
            $this->active_object->restoreFromArchive();

            return $this->active_object;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Move to trash.
     *
     * @return ApplicationObject|IArchive|int|ITrash
     */
    public function trash(Request $request, User $user)
    {
        if ($this->active_object instanceof ITrash && $this->active_object->canTrash($user)) {
            $this->active_object->moveToTrash($user);

            return $this->active_object;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Restore from trash.
     *
     * @return ApplicationObject|IArchive|int|ITrash
     */
    public function restore_from_trash(Request $request, User $user)
    {
        if ($this->active_object instanceof ITrash && $this->active_object->canRestoreFromTrash($user)) {
            $this->active_object->restoreFromTrash();

            return $this->active_object;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Permanently delete an individual object.
     *
     * @return int
     */
    public function permanently_delete(Request $request, User $user)
    {
        if ($this->active_object->canDelete($user) && $this->active_object instanceof ITrash && $this->active_object->getIsTrashed()) {
            $this->active_object->delete();

            return Response::OK;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Reactivate the object.
     *
     * @return DataObject|int
     */
    public function reactivate(Request $request, User $user)
    {
        if ($this->active_object->canEdit($user) && ($this->active_object instanceof IArchive || $this->active_object instanceof ITrash)) {
            return DataManager::reactivate($this->active_object);
        }

        return Response::NOT_FOUND;
    }
}
