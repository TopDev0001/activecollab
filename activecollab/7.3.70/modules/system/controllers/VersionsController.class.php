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
 * Versions controller.
 *
 * @package ActiveCollab.system
 * @subpackage controllers
 */
class VersionsController extends AuthRequiredController
{
    /**
     * @var Versions
     */
    protected $versions_model;

    public function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof Client) {
            return Response::NOT_FOUND;
        }

        $this->versions_model = new Versions();

        return null;
    }

    /**
     * Check if there old versions in ActiveCollab folder.
     *
     * @return array
     */
    public function check_old_versions()
    {
        return $this->versions_model->checkOldVersions();
    }

    /**
     * Delete All Old Versions from ActiveCollab folder.
     *
     * @return array
     */
    public function delete_old_versions()
    {
        return $this->versions_model->deleteOldVersions();
    }
}
