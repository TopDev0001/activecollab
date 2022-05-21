<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Expense categories controller.
 *
 * @package ActiveCollab.modules tracking
 * @subpackage controllers
 */
final class ExpenseCategoriesController extends AuthRequiredController
{
    /**
     * @var ExpenseCategory
     */
    protected $active_expense_category;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_expense_category = DataObjectPool::get('ExpenseCategory', $request->getId('expense_category_id'));
        if (empty($this->active_expense_category)) {
            $this->active_expense_category = new ExpenseCategory();
        }
    }

    /**
     * List expense categories.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return ExpenseCategories::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * Add expense category
     * If expense category exist with same name, remove it from archive.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return ExpenseCategories::canAdd($user) ? ExpenseCategories::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * View expense category.
     *
     * @return ExpenseCategory|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_expense_category->isLoaded() && $this->active_expense_category->canView($user) ? $this->active_expense_category : Response::NOT_FOUND;
    }

    /**
     * Edit expense category.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_expense_category->isLoaded() && $this->active_expense_category->canEdit($user) ? ExpenseCategories::update($this->active_expense_category, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Batch edit expense categories.
     *
     * @return array|ExpenseCategory[]
     */
    public function batch_edit(Request $request, User $user)
    {
        return $user->isOwner() ? ExpenseCategories::batchEdit($request->put()) : [];
    }

    /**
     * Delete expense category
     * If expense category is used, move it to archive.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_expense_category->isLoaded() && $this->active_expense_category->canDelete($user) ? ExpenseCategories::scrap($this->active_expense_category) : Response::NOT_FOUND;
    }

    /**
     * @return ExpenseCategory|int
     */
    public function view_default()
    {
        if ($expense_category = DataObjectPool::get('ExpenseCategory', ExpenseCategories::getDefaultId())) {
            return $expense_category;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Set as default expense category.
     *
     * @return ExpenseCategory|int
     */
    public function set_default(Request $request, User $user)
    {
        if ($user->isOwner()) {
            /** @var ExpenseCategory $expense_category */
            if ($expense_category = DataObjectPool::get('ExpenseCategory', $request->post('expense_category_id'))) {
                return ExpenseCategories::setDefault($expense_category);
            }

            return Response::BAD_REQUEST;
        }

        return Response::NOT_FOUND;
    }
}
