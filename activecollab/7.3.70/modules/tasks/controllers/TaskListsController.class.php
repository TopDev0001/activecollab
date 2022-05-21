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

AngieApplication::useController('project', SystemModule::NAME);

class TaskListsController extends ProjectController
{
    use MoveToProjectControllerAction;

    /**
     * Selected task list.
     *
     * @var TaskList
     */
    protected $active_task_list;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        $this->active_task_list = DataObjectPool::get(TaskList::class, $request->getId('task_list_id'));

        if (empty($this->active_task_list)) {
            $this->active_task_list = new TaskList();
            $this->active_task_list->setProject($this->active_project);
        }

        if ($this->active_task_list->getProjectId() != $this->active_project->getId()) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * Show task lists index page.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        return TaskLists::prepareCollection('active_task_lists_in_project_' . $this->active_project->getId(), $user);
    }

    /**
     * Show task lists index page.
     *
     * @return ModelCollection|void
     */
    public function archive(Request $request, User $user)
    {
        return TaskLists::prepareCollection('archived_task_lists_in_project_' . $this->active_project->getId() . '_page_' . $request->getPage(), $user);
    }

    /**
     * Show all open and closed task lists.
     *
     * @return ModelCollection|void
     */
    public function all_task_lists(Request $request, User $user)
    {
        return TaskLists::prepareCollection('all_task_lists_in_project_' . $this->active_project->getId(), $user);
    }

    /**
     * Reorder task lists.
     *
     * @return int
     */
    public function reorder(Request $request, User $user)
    {
        if (TaskLists::canReorder($user, $this->active_project)) {
            TaskLists::reorder($request->put(), $this->active_project, $user);

            return $request->put();
        }

        return Response::NOT_FOUND;
    }

    /**
     * Show single task list.
     *
     * @return int|TaskList
     */
    public function view(Request $request, User $user)
    {
        return $this->active_task_list->isLoaded() && $this->active_task_list->canView($user) ? $this->active_task_list : Response::NOT_FOUND;
    }

    /**
     * @return ModelCollection|int
     */
    public function open_tasks(Request $request, User $user)
    {
        if (!$this->active_task_list->isLoaded() || !$this->active_task_list->canView($user)) {
            return Response::NOT_FOUND;
        }

        return Tasks::prepareCollection('open_tasks_in_task_list_' . $this->active_task_list->getId(), $user);
    }

    /**
     * @return ModelCollection|int
     */
    public function completed_tasks(Request $request, User $user)
    {
        return $this->active_task_list->isLoaded() && $this->active_task_list->canView($user) ?
            Tasks::prepareCollection('archived_tasks_in_task_list_' . $this->active_task_list->getId() . '_page_' . $request->getPage(), $user)
            : Response::NOT_FOUND;
    }

    /**
     * Create a new task list.
     *
     * @return TaskList|int
     */
    public function add(Request $request, User $user)
    {
        if (TaskLists::canAdd($user, $this->active_project)) {
            $post = $request->put();

            if (is_array($post)) {
                $post['project_id'] = $this->active_project->getId();
            }

            return TaskLists::create($post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Edit specific task list.
     *
     * @return TaskList|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_task_list->isLoaded() && $this->active_task_list->canEdit($user)
            ? TaskLists::update($this->active_task_list, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Edit specific task list.
     *
     * @return int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_task_list->isLoaded() && $this->active_task_list->canDelete($user)
            ? TaskLists::scrap($this->active_task_list)
            : Response::NOT_FOUND;
    }

    public function duplicate(Request $request, User $user)
    {
        $project = $this->active_project;
        $task_list_to_copy = $this->getObjectToBeMoved();

        if (!$task_list_to_copy->canCopyToProject($user, $project)) {
            return Response::FORBIDDEN;
        }

        $new_task_list_name = trim((string) $request->post('name'));

        if (empty($new_task_list_name)) {
            $new_task_list_name = null;
        }

        try {
            if ($this->isDiskLimitExceeded($this->active_task_list)) {
                return $this->getStatusResponse($user);
            }

            return $task_list_to_copy->duplicate($user, $new_task_list_name);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Error while duplicate task list.',
                [
                    'project_id' => $project->getId(),
                    'task_list_id' => $task_list_to_copy->getId(),
                    'new_list_name' => $new_task_list_name,
                    'trace' => $e,
                ]
            );

            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                [
                    'message' => lang('Unable to duplicate task list.'),
                    'type' => 'error', // because angular only handles error responses with type error or exception
                ]
            );
        }
    }

    /**
     * @return TaskList
     */
    public function &getObjectToBeMoved()
    {
        return $this->active_task_list;
    }

    public function insert(Request $request, User $user)
    {
        if (TaskLists::canAdd($user, $this->active_project)) {
            $post = $request->put();

            if (is_array($post)) {
                $post['project_id'] = $this->active_project->getId();
            }

            if (!array_key_exists('position', $post)) {
                return Response::BAD_REQUEST;
            }

            return TaskLists::insert($user, $post);
        }

        return Response::NOT_FOUND;
    }
}
