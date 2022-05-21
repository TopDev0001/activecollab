<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Controller\Error;

use Angie\Error;

/**
 * Controller does not exist error, thrown when controller is missing.
 *
 * @package angie.library.controller
 * @subpackage errors
 */
class ControllerNotFound extends Error
{
    /**
     * @param string      $controller
     * @param string|null $message
     */
    public function __construct($controller, $message = null)
    {
        if (empty($message)) {
            $message = "Controller '$controller' is missing";
        }

        parent::__construct($message, ['controller' => $controller]);
    }
}
