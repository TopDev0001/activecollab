<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Traits\ParentFromGetRequest;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Application level reactions controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
final class ReactionsController extends AuthRequiredController
{
    use ParentFromGetRequest;

    /**
     * @var DataObject|IReactions
     */
    private $parent;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->parent = $this->getParentFromRequest($request, IReactions::class);

        if (!$this->parent) {
            return Response::NOT_FOUND;
        }

        if (!$this->parent->canReact($user)) {
            return Response::FORBIDDEN;
        }
    }

    /**
     * @return Reaction|int
     */
    public function add_reaction(Request $request, User $user)
    {
        $post = $request->post();

        if (
            empty($post['type'])
            || !in_array($post['type'], IReactions::REACTION_TYPES)
            || $this->parent->getExistingReactionByUser($post['type'], $user->getId())
        ) {
            return Response::BAD_REQUEST;
        }

        try {
            return $this->parent->submitReaction($user, $post);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to add reaction.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return Response::BAD_REQUEST;
        }
    }

    /**
     * @return int
     */
    public function remove_reaction(Request $request, User $user)
    {
        $body = json_decode($request->getBody()->getContents(), true);
        $body = $body ? $body : $request->post();

        if (empty($body['type']) || !in_array($body['type'], IReactions::REACTION_TYPES)) {
            return Response::BAD_REQUEST;
        }

        $reaction = $this->parent->getExistingReactionByUser($body['type'], $user->getId());

        if (!$reaction) {
            return Response::NOT_FOUND;
        }

        try {
            return $reaction->delete();
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to delete reaction.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return Response::BAD_REQUEST;
        }
    }
}
