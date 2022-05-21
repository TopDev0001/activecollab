<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;

AngieApplication::useController('selected_object', EnvironmentFramework::INJECT_INTO);

class HistoryController extends SelectedObjectController
{
    /**
     * Selected object.
     *
     * @var DataObject|IHistory
     */
    protected $active_object;

    protected string $active_object_instance_of = IHistory::class;

    public function index(Request $request, User $user)
    {
        if ($request->get('verbose')) {
            return $this->active_object->getVerboseHistory($user);
        }

        return $this->active_object->getHistory($user);
    }
}
