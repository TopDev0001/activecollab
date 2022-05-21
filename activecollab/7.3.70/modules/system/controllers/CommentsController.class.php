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
 * Commnents controller delegate implementation.
 *
 * @package activeCollab.modules.system
 * @subpackage models
 */
final class CommentsController extends AuthRequiredController
{
    /**
     * @var Comment
     */
    protected $active_comment;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_comment = DataObjectPool::get('Comment', $request->getId('comment_id'));
    }

    /**
     * Show parent comments.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        if ($parent = $this->parentFromGet($request, $user)) {
            return Comments::prepareCollection('comments_for_' . $parent->getModelName(false, true) . '-' . $parent->getId() . '_page_' . $request->getPage(), $user);
        }

        return Response::NOT_FOUND;
    }

    /**
     * View single comment.
     *
     * @return Comment|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_comment instanceof Comment && $this->active_comment->isLoaded() && $this->active_comment->canView($user) ? $this->active_comment : Response::NOT_FOUND;
    }

    /**
     * Create new comment.
     *
     * @return Comment|int
     */
    public function add(Request $request, User $user)
    {
        $parent = $this->parentFromGet($request, $user);

        if ($parent instanceof IComments && $parent->canComment($user)) {
            $post = $request->post();
            $comment_body = array_var($post, 'body', '', true);

            return $parent->submitComment($comment_body, $user, $post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Update an existing comment.
     *
     * @return Comment|int
     */
    public function edit(Request $request, User $user)
    {
        if ($this->active_comment instanceof Comment && $this->active_comment->isLoaded() && $this->active_comment->canEdit($user)) {
            return Comments::update($this->active_comment, $request->put());
        }

        return Response::NOT_FOUND;
    }

    /**
     * Drop active comment.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_comment instanceof Comment && $this->active_comment->isLoaded() && $this->active_comment->canDelete($user) ? Comments::scrap($this->active_comment) : Response::NOT_FOUND;
    }

    /**
     * Return parent from GET parameters.
     *
     * @return DataObject|IComments|null
     */
    private function parentFromGet(Request $request, User $user)
    {
        $parent_type = $request->get('parent_type');

        if ($parent_type) {
            $parent_type = Angie\Inflector::camelize(str_replace('-', '_', $parent_type));
        }

        $parent_id = $request->getId('parent_id');

        if (class_exists($parent_type) && is_subclass_of($parent_type, 'DataObject')) {
            $parent = DataObjectPool::get($parent_type, $parent_id);

            if ($parent instanceof IComments && $parent->canView($user)) {
                return $parent;
            }
        }

        return null;
    }
}
