<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('selected_object', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level complete controller delegate implementation.
 *
 * @package angie.frameworks.complete
 * @subpackage controllers
 */
class FwCompleteController extends SelectedObjectController
{
    /**
     * Active object instance.
     *
     * @var ApplicationObject|IComplete
     */
    protected $active_object;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!($this->active_object instanceof IComplete && $this->active_object->canChangeCompletionStatus($user))) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * Mark active object as completed.
     *
     * @return DataObject|IComplete|int
     */
    public function complete(Request $request, User $user)
    {
        if ($this->active_object->isOpen()) {
            $this->active_object->complete($user);
        }

        return $this->active_object;
    }

    /**
     * Mark active object as open.
     *
     * @return DataObject|IComplete|int
     */
    public function open(Request $request, User $user)
    {
        if ($this->active_object->isCompleted()) {
            $this->active_object->open($user);
        }

        return $this->active_object;
    }
}
