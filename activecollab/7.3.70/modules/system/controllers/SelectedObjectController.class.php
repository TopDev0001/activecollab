<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class SelectedObjectController extends AuthRequiredController
{
    /**
     * Selected object.
     *
     * @var DataObject
     */
    protected $active_object;

    protected string $active_object_instance_of = DataObject::class;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $parent_type = $request->get('parent_type');

        if ($parent_type) {
            $parent_type = Angie\Inflector::camelize(str_replace('-', '_', $parent_type));
        }

        $parent_id = $request->getId('parent_id');

        if (class_exists($parent_type) && is_subclass_of($parent_type, DataObject::class)) {
            $this->active_object = DataObjectPool::get($parent_type, $parent_id);
        }

        if (!$this->isValidObject($this->active_object, $user)) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    private function isValidObject($object, User $user): bool
    {
        if (!$object instanceof $this->active_object_instance_of) {
            return false;
        }

        if (!method_exists($object, 'canView') || !$object->canView($user)) {
            return false;
        }

        return true;
    }
}
