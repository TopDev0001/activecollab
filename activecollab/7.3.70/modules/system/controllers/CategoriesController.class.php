<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class CategoriesController extends AuthRequiredController
{
    protected ?Category $active_category = null;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_category = DataObjectPool::get(Category::class, $request->getId('category_id'));

        return null;
    }

    public function index()
    {
        return Response::NOT_FOUND;
    }

    /**
     * Display a specific category.
     *
     * @return Category|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_category instanceof Category && $this->active_category->isLoaded() && $this->active_category->canView($user)
            ? $this->active_category
            : Response::NOT_FOUND;
    }

    /**
     * Add category.
     *
     * @return Category|int
     */
    public function add(Request $request, User $user)
    {
        $post = $request->post();

        if (is_array($post) && isset($post['type']) && $post['type']) {
            $type = $post['type'];

            if (Categories::canManage($user, $type)) {
                return Categories::create($post);
            }
        }

        return Response::NOT_FOUND;
    }

    /**
     * Update selected category.
     *
     * @return Category|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_category instanceof Category && $this->active_category->isLoaded() && $this->active_category->canEdit($user)
            ? Categories::update($this->active_category, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Delete selected category.
     *
     * @return int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_category instanceof Category && $this->active_category->isLoaded() && $this->active_category->canDelete($user)
            ? Categories::scrap($this->active_category)
            : Response::NOT_FOUND;
    }
}
