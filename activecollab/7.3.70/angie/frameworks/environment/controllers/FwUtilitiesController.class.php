<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('auth_not_required', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level utilities controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwUtilitiesController extends AuthNotRequiredController
{
    /**
     * @return array
     */
    public function info()
    {
        return ['application' => AngieApplication::getName(), 'version' => AngieApplication::getVersion()];
    }
}
