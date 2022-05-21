<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\Tracking\Utils\TrackingBillableStatusResolver\TrackingBillableStatusResolverInterface;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('project', SystemModule::NAME);

final class ExpensesController extends ProjectController
{
    /**
     * Selected expense instance.
     *
     * @var Expense
     */
    protected $active_expense;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        $this->active_expense = DataObjectPool::get(Expense::class, $request->getId('expense_id'));

        if (empty($this->active_expense)) {
            $this->active_expense = new Expense();
            $this->active_expense->setParent($this->active_project);
        }

        if (!$this->active_expense->getProject()->is($this->active_project)) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * List project expenses.
     *
     * @return ExpensesCollection|int
     */
    public function index(Request $request, User $user)
    {
        if ($user instanceof Client && !$this->active_project->getIsClientReportingEnabled()) {
            return Response::NOT_FOUND;
        }

        AccessLogs::logAccess($this->active_project, $user);

        return Expenses::prepareCollection('expenses_in_project_' . $this->active_project->getId() . '_page_' . $request->getPage(), $user);
    }

    /**
     * Log a new expense.
     *
     * @return DataObject|int
     * @throws InvalidParamError
     */
    public function add(Request $request, User $user)
    {
        $post = $request->post();

        if (isset($post['task_id']) && $post['task_id']) {
            $track_expense_for = DataObjectPool::get('Task', $post['task_id']);
        }

        if (array_key_exists('user_id', $post)) {
            /** @var User $assigned_user */
            $assigned_user = Users::findById($post['user_id']);

            if ($assigned_user) {
                if ($assigned_user->getId() != $user->getId()
                    && !Expenses::canTrackForOthers($user, $this->active_project)
                ) {
                    return Response::FORBIDDEN;
                }

                $post['user_id'] = $assigned_user->getId();
                $post['user_name'] = $assigned_user->getFullName() ? $assigned_user->getFullName() : null;
                $post['user_email'] = $assigned_user->getEmail();
            } else {
                return Response::BAD_REQUEST;
            }
        } else {
            $post['user_id'] = $user->getId();
            $post['user_name'] = $user->getFullName() ? $user->getFullName() : null;
            $post['user_email'] = $user->getEmail();
        }

        if (empty($track_expense_for)) {
            $track_expense_for = $this->active_project;
        }

        if ($track_expense_for->canTrackExpenses($user)) {
            $post['parent_type'] = get_class($track_expense_for);
            $post['parent_id'] = $track_expense_for->getId();

            if (!array_key_exists('billable_status', $post)) {
                $post['billable_status'] = $this->active_project->getDefaultBillableStatus();
            }

            $post['billable_status'] = AngieApplication::getContainer()
                ->get(TrackingBillableStatusResolverInterface::class)
                ->getBillabeStatus($user, $track_expense_for, (int) $post['billable_status']);

            return Expenses::create($post);
        } else {
            return Response::NOT_FOUND;
        }
    }

    /**
     * Show expense data.
     *
     * @return int|Expense
     */
    public function view(Request $request, User $user)
    {
        return $this->active_expense->isLoaded() && $this->active_expense->canView($user) ? $this->active_expense : Response::NOT_FOUND;
    }

    /**
     * Update a selected expense.
     *
     * @return DataObject|int
     * @throws InvalidParamError
     */
    public function edit(Request $request, User $user)
    {
        if ($this->active_expense->isLoaded() && $this->active_expense->canEdit($user)) {
            $put = $request->put();

            if (array_key_exists('user_id', $put)) {
                /** @var User $new_user */
                $new_user = Users::findById($put['user_id']);

                if ($new_user) {
                    if ($new_user->getId() != $this->active_expense->getUserId()
                        && !Expenses::canTrackForOthers($user, $this->active_project)
                    ) {
                        return Response::FORBIDDEN;
                    }

                    $put['user_id'] = $new_user->getId();
                } else {
                    return Response::BAD_REQUEST;
                }
            }

            foreach (['parent_type', 'parent_id', 'task_id', 'project_id'] as $k) {
                if (array_key_exists($k, $put)) {
                    unset($put[$k]);
                }
            }

            /** @var User $assigned_user */
            $assigned_user = Users::findById($put['user_id']);
            if ($assigned_user) {
                $put['user_name'] = $assigned_user->getFullName() ? $assigned_user->getFullName() : null;
                $put['user_email'] = $assigned_user->getEmail();
            }

            if (array_key_exists('billable_status', $put)) {
                $put['billable_status'] = AngieApplication::getContainer()
                    ->get(TrackingBillableStatusResolverInterface::class)
                    ->getBillabeStatusForTrackingObject(
                        $user,
                        $this->active_expense,
                        (int) $put['billable_status']
                    );
            }

            return Expenses::update($this->active_expense, $put);
        }

        return Response::NOT_FOUND;
    }

    /**
     * @return Expense|int
     * @throws InvalidInstanceError
     */
    public function move(Request $request, User $user)
    {
        if ($this->active_expense->isLoaded() && $this->active_expense->canEdit($user)) {
            $move_to = $this->getMoveToParentFromPut($request->put());

            if ($move_to instanceof ITracking) {
                $this->active_expense->setParent($move_to, true);
            } else {
                return Response::BAD_REQUEST;
            }

            return $this->active_expense;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Return target task or project from PUT parameters.
     *
     * @param  array                        $put
     * @return Project|Task|DataObject|null
     */
    private function getMoveToParentFromPut($put)
    {
        if (array_key_exists('task_id', $put)) {
            if ($put['task_id']) {
                return DataObjectPool::get(Task::class, $put['task_id']);
            }

            return DataObjectPool::get(Project::class, (isset($put['project_id']) ? $put['project_id'] : null));
        }

        if (array_key_exists('project_id', $put)) {
            return DataObjectPool::get(Project::class, $put['project_id']);
        }

        return null;
    }

    /**
     * Move selected expense to trash.
     *
     * @return bool|DataObject|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_expense->isLoaded() && $this->active_expense->canDelete($user)
            ? Expenses::scrap($this->active_expense)
            : Response::NOT_FOUND;
    }
}
