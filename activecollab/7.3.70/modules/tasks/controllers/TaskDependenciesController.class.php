<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', SystemModule::NAME);

class TaskDependenciesController extends AuthRequiredController
{
    /**
     * @var Task
     */
    protected $active_task;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        $this->active_task = DataObjectPool::get(Task::class, $request->getId('task_id'));

        if (empty($this->active_task)) {
            return Response::NOT_FOUND;
        }

        if (!$this->active_task->canView($user)) {
            return Response::FORBIDDEN;
        }

        return null;
    }

    public function view(Request $request, $user)
    {
        return AngieApplication::taskDependenciesResolver($user)->getDependencies($this->active_task);
    }

    public function dependency_suggestions(Request $request, $user)
    {
        return AngieApplication::taskDependenciesResolver($user)->getDependencySuggestions($this->active_task);
    }

    public function create(Request $request, $user)
    {
        $post = $request->post();

        $dependent_task = $this->getDependentTask($post);

        if (!$dependent_task) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                [
                    'message' => "Dependent task does not exist or it isn't from the same project",
                ]
            );
        }

        if (!$this->active_task->canEdit($user) || !$dependent_task->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        try {
            $is_parent = array_key_exists('is_parent', $post) ? (bool) $post['is_parent'] : false;

            $parent = $is_parent ? $dependent_task : $this->active_task;
            $child = !$is_parent ? $dependent_task : $this->active_task;

            return TaskDependencies::createDependency($parent, $child, $user);
        } catch (LogicException $e) {
            return new StatusResponse(
                Response::NOT_ACCEPTABLE,
                '',
                ['message' => $e->getMessage()]
            );
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Error while create dependency for task {task_id}.',
                [
                    'task_id' => $this->active_task->getId(),
                    'dependency_task_id' => $dependent_task->getId(),
                    'trace' => $e,
                ]
            );

            return Response::NOT_ACCEPTABLE;
        }
    }

    public function delete(Request $request, $user)
    {
        $dependent_task = $this->getDependentTask($request->put());

        if (!$dependent_task) {
            return Response::BAD_REQUEST;
        }

        if (!$this->active_task->canEdit($user) || !$dependent_task->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        TaskDependencies::deleteDependency($this->active_task, $dependent_task, $user);

        return Response::OK;
    }

    /**
     * @return Task|null
     */
    private function getDependentTask(array $payload)
    {
        if (!array_key_exists('dependency_id', $payload)) {
            return null;
        }

        $dependency_id = (int) $payload['dependency_id'];

        /** @var Task $task */
        $task = DataObjectPool::get(Task::class, $dependency_id);

        if (empty($task) || $task->getProjectId() !== $this->active_task->getProjectId() || $task->getIsTrashed()) {
            return null;
        }

        return $task;
    }
}
