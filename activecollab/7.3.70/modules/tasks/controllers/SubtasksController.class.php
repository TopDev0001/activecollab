<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\App\Channel\OnDemandChannelInterface;
use ActiveCollab\Module\Tasks\TasksModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('tasks', TasksModule::NAME);

/**
 * Subtasks controller delegate.
 *
 * @package angie.frameworks.subtasks
 * @subpackage controllers
 */
class SubtasksController extends TasksController
{
    /**
     * Selected subtask.
     *
     * @var Subtask
     */
    protected $active_subtask;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($this->active_task->isNew() || !$this->active_task->canView($user)) {
            return Response::NOT_FOUND;
        }

        $this->active_subtask = DataObjectPool::get('Subtask', $request->getId('subtask_id'));

        if ($this->active_subtask instanceof Subtask) {
            if ($this->active_subtask->getTaskId() != $this->active_task->getId()) {
                return Response::NOT_FOUND;
            }
        } else {
            $this->active_subtask = new Subtask();
            $this->active_subtask->setTask($this->active_task);
        }
    }

    /**
     * List all subtasks.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        return Subtasks::prepareCollection('subtasks_for_task_' . $this->active_task->getId(), $user);
    }

    /**
     * Reorder subtasks.
     *
     * @return array|int
     */
    public function reorder(Request $request, User $user)
    {
        $source_subtask = DataObjectPool::get(Subtask::class, $request->put('source_subtask_id'));
        $target_subtask = DataObjectPool::get(Subtask::class, $request->put('target_subtask_id'));
        $before = $request->put('before');

        if (!$source_subtask instanceof Subtask || !$target_subtask instanceof Subtask) {
            return Response::BAD_REQUEST;
        }

        return Subtasks::reorder($source_subtask, $target_subtask, $before);
    }

    /**
     * Create a new subtask instance.
     *
     * @return Subtask|int|StatusResponse
     */
    public function add(Request $request, User $user)
    {
        if ($this->active_task->isCompleted()) {
            return new StatusResponse(
                Response::CONFLICT,
                '',
                [
                    'message' => lang('Cannot create new subtask on completed task.'),
                    'type' => 'error',
                ]
            );
        }

        if ($this->active_task->canEdit($user)) {
            $post = $request->post();
            $post['task_id'] = $this->active_task->getId();

            return Subtasks::create($post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * View task URL (redirects to parent object).
     *
     * @return int|Subtask
     */
    public function view(Request $request, User $user)
    {
        return $this->active_subtask->isLoaded() ? $this->active_subtask : Response::NOT_FOUND;
    }

    /**
     * Updated a subtask.
     *
     * @return Subtask|DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_subtask->isLoaded() && $this->active_subtask->canEdit($user)
            ? Subtasks::update($this->active_subtask, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Delete a single subtask.
     *
     * @return Subtask|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_subtask->isLoaded() && $this->active_subtask->canDelete($user)
            ? Subtasks::scrap($this->active_subtask)
            : Response::NOT_FOUND;
    }

    /**
     * @return Task|int|StatusResponse
     */
    public function promote_to_task(Request $request, User $user)
    {
        if ($this->active_subtask->isLoaded() && $this->active_task->canEdit($user) && Tasks::canAdd($user, $this->active_project)) {
            try {
                return Subtasks::promoteToTask($this->active_subtask, $this->active_task, $user);
            } catch (Exception $e) {
                AngieApplication::log()->error(
                    'Promote to task error: {error}',
                    [
                        'trace' => $e,
                        'error' => $e->getMessage(),
                    ]
                );

                return new StatusResponse(
                    Response::BAD_REQUEST,
                    '',
                    [
                        'message' => AngieApplication::getContainer()->get(OnDemandChannelInterface::class)->isEdgeChannel()
                            ? sprintf('Failed to convert. Reason: %s.', $e->getMessage())
                            : 'Something went wrong. Please try again.',
                    ]
                );
            }
        }

        return Response::FORBIDDEN;
    }
}
