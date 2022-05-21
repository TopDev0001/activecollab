<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Controller\Error;

use Angie\Error;

/**
 * Controller action does not exist error.
 *
 * @package angie.library.controller
 * @subpackage errors
 */
class ActionNotFound extends Error
{
    /**
     * @param string      $controller
     * @param string      $action
     * @param string|null $message
     */
    public function __construct($controller, $action, $message = null)
    {
        if (empty($message)) {
            $message = "Invalid controller action $controller::$action()";
        }

        parent::__construct($message, ['controller' => $controller, 'action' => $action]);
    }
}
