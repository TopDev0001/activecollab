<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Model\FeaturePointer\FeaturePointerInterface;
use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', SystemModule::NAME);

class FeaturePointersController extends AuthRequiredController
{
    /**
     * @var FeaturePointerInterface
     */
    private $active_feature_pointer;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($feature_pointer_id = $request->getId('feature_pointer_id')) {
            $this->active_feature_pointer = FeaturePointers::findById($feature_pointer_id);
        }
    }

    public function index(Request $request, User $user)
    {
        return FeaturePointers::prepareCollection('feature_pointers_for_user', $user);
    }

    public function dismiss(Request $request, User $user)
    {
        return $this->active_feature_pointer instanceof FeaturePointerInterface &&
        $this->active_feature_pointer->isLoaded() &&
        $this->active_feature_pointer->shouldShow($user)
            ? FeaturePointers::dismiss($this->active_feature_pointer, $user)
            : Response::NOT_FOUND;
    }
}
