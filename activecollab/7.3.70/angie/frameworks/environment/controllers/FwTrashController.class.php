<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Trash;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level trash controller implementation.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwTrashController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->canUseTrash()) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * List trash content.
     *
     * @return array
     */
    public function show_content(Request $request, User $user)
    {
        return Trash::getObjects($user);
    }

    /**
     * Permanently remove all objects.
     *
     * @return array
     */
    public function empty_trash(Request $request, User $user)
    {
        Trash::emptyTrash($user, $request->get('delete_per_iteration'));

        return Response::OK;
    }
}
