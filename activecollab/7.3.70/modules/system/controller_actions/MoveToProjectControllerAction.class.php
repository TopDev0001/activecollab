<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\OnDemand\Utils\StorageOverUsageMessageResolver\StorageOverUsageMessageResolverInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;
use Angie\Storage\OveruseResolver\StorageOveruseResolverInterface;

/**
 * Move to project controller action.
 *
 * @package activeCollab.modules.system
 * @subpackage actions
 */
trait MoveToProjectControllerAction
{
    /**
     * Move object to project.
     *
     * @return DataObject|IProjectElement|int|StatusResponse
     */
    public function move_to_project(Request $request, User $user)
    {
        /** @var DataObject|IProjectElement|ITrash $object_to_be_moved */
        $object_to_be_moved = $this->getObjectToBeMoved();
        if ($this->canBeMovedOrCopied($object_to_be_moved)) {
            $target_project = DataObjectPool::get(Project::class, $request->put('project_id'));

            if ($target_project instanceof Project) {
                if ($request->put('copy')) {
                    if ($object_to_be_moved->canCopyToProject($user, $target_project)) {
                        if ($this->isDiskLimitExceeded($object_to_be_moved)) {
                            return $this->getStatusResponse($user);
                        }

                        return $object_to_be_moved->copyToProject($target_project, $user);
                    }
                } else {
                    if ($object_to_be_moved->canMoveToProject($user, $target_project)) {
                        $object_to_be_moved->moveToProject($target_project, $user);

                        return $object_to_be_moved;
                    }
                }
            }
        }

        return Response::NOT_FOUND;
    }

    /**
     * Return object that needs to be moved.
     *
     * @return IProjectElement
     */
    abstract public function &getObjectToBeMoved();

    protected function canBeMovedOrCopied($object_to_be_moved): bool
    {
        return $object_to_be_moved instanceof IProjectElement
            && $object_to_be_moved instanceof DataObject
            && $object_to_be_moved->isLoaded()
            && !$object_to_be_moved->getIsTrashed();
    }

    protected function isDiskLimitExceeded($object_to_be_moved): bool
    {
        if (AngieApplication::isOnDemand() && AngieApplication::getContainer()->get(StorageOveruseResolverInterface::class)->isDiskFull(true)) {
           if ($object_to_be_moved instanceof File) {
               return true;
           }

            if ($object_to_be_moved instanceof Task || $object_to_be_moved instanceof Note || $object_to_be_moved instanceof Discussion) {
                return !empty($object_to_be_moved->getAttachments());
            }

            if ($object_to_be_moved instanceof TaskList) {
                $tasks = Tasks::find(
                    [
                        'conditions' => [
                            'task_list_id = ?',
                            $object_to_be_moved->getId(),
                        ],
                    ]
                );

                if (!empty($tasks)) {
                    foreach ($tasks as $task) {
                        if (!empty($task->getAttachments())) {
                            return true;
                        }
                    }
                }

                return false;
            }
        }

        return false;
    }

    private function getStatusResponse(User $user): StatusResponse
    {
        return new StatusResponse(
            Response::BAD_REQUEST,
            '',
            [
                'type' => 'StorageOverusedError',
                'error' => 'storage_overused',
                'error_content' => AngieApplication::getContainer()->get(StorageOverUsageMessageResolverInterface::class)->resolve($user),
                'message' => lang('Disk is full!'),
            ]
        );
    }
}
