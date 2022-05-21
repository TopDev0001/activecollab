<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Error;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Storage\OveruseResolver\StorageOveruseResolverInterface;

AngieApplication::useController('project', SystemModule::NAME);

class RecurringTasksController extends ProjectsController
{
    /**
     * Active recurring task.
     *
     * @var RecurringTask
     */
    protected $active_recurring_task;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        $this->active_recurring_task = DataObjectPool::get('RecurringTask', $request->getId('recurring_task_id'));

        if ($this->active_recurring_task instanceof RecurringTask) {
            if ($this->active_recurring_task->getProjectId() !== $this->active_project->getId()) {
                return Response::NOT_FOUND;
            }
        } else {
            $this->active_recurring_task = new RecurringTask();
            $this->active_recurring_task->setProject($this->active_project);
        }
    }

    /**
     * Show recurring tasks index page.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        AccessLogs::logAccess($this->active_project, $user);

        return RecurringTasks::prepareCollection('project_recurring_tasks_' . $this->active_project->getId(), $user);
    }

    /**
     * Show single recurring task.
     *
     * @return int|RecurringTask
     */
    public function view(Request $request, User $user)
    {
        return $this->active_recurring_task->isLoaded() && $this->active_recurring_task->canView($user)
            ? AccessLogs::logAccess($this->active_recurring_task, $user)
            : Response::NOT_FOUND;
    }

    /**
     * Create a new recurring task.
     *
     * @return int|RecurringTask
     */
    public function add(Request $request, User $user)
    {
        if (!RecurringTasks::canAdd($user, $this->active_project)) {
            return Response::NOT_FOUND;
        }

        $post = $request->post();

        if ($post && is_array($post)) {
            $post['project_id'] = $this->active_project->getId();
            unset($post['created_by_id'], $post['created_by_email'], $post['created_by_name']);
        }

        return RecurringTasks::create($post);
    }

    /**
     * Update existing recurring task.
     *
     * @return RecurringTask|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_recurring_task->isLoaded() && $this->active_recurring_task->canEdit($user)
            ? RecurringTasks::update($this->active_recurring_task, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Move recurring task to trash.
     *
     * @return DataObject|int|bool
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_recurring_task->isLoaded() && $this->active_recurring_task->canDelete($user)
            ? RecurringTasks::scrap($this->active_recurring_task) : Response::NOT_FOUND;
    }

    /**
     * Create task from recurring task - create one.
     *
     * @return RecurringTask|DataObject|int
     */
    public function create_task(Request $request, User $user)
    {
        if (empty($request->post('name'))) {
            return Response::BAD_REQUEST;
        }

        if ($this->active_recurring_task->isLoaded() && $this->active_recurring_task->canEdit($user)) {
            if ($this->active_recurring_task->countAttachments()
                && AngieApplication::getContainer()->get(StorageOveruseResolverInterface::class)->isDiskFull(true)
            ) {
                throw new Error("Can't create task with attachments, check storage restriction for your plan.");
            }

            $override_name = trim($request->post('name'));

            if (empty($override_name)) {
                $override_name = null;
            }

            return $this->active_recurring_task->createTask(
                null,
                $user,
                $override_name
            );
        } else {
            return Response::NOT_FOUND;
        }
    }
}
