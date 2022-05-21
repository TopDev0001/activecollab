<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', SystemModule::NAME);

class TrackingByParentController extends AuthRequiredController
{
    protected ITracking $parent;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $parent_type = Angie\Inflector::camelize(
            str_replace('-', '_', strtolower($request->get('parent_type')))
        );

        if (class_exists($parent_type) && is_subclass_of($parent_type, DataObject::class)) {
            $parent = DataObjectPool::get($parent_type, $request->get('parent_id'));

            if ($parent instanceof ITracking && $parent->canView($user)) {
                $this->parent = $parent;

                $project = $this->parent instanceof Task ? $this->parent->getProject() : $this->parent;

                if ($user->isClient() && !$project->getIsClientReportingEnabled()) {
                    return Response::FORBIDDEN;
                }
            } else {
                return Response::FORBIDDEN;
            }
        } else {
            return Response::NOT_FOUND;
        }

        return null;
    }

    protected function prepareQueryParams(Request $request): array
    {
        $from_string = $request->get('from');
        $to_string = $request->get('to');

        $from = $from_string ? DateValue::makeFromString($from_string) : null;
        $to = $to_string ? DateValue::makeFromString($to_string) : null;

        return [$from, $to];
    }
}
