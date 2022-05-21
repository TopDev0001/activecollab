<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Controller;

use Angie\Controller\Error\ActionNotFound;
use Angie\Http\Request;

/**
 * Controller implementation.
 *
 * @package angie.library.controller
 */
interface ControllerInterface
{
    /**
     * Execute action.
     *
     * @param  string         $action
     * @return mixed
     * @throws ActionNotFound
     */
    public function executeAction($action, Request $request);

    /**
     * Return controller name based on controller class name.
     *
     * @param  string|null $controller_class_name
     * @return string
     */
    public function getName($controller_class_name = null);
}
