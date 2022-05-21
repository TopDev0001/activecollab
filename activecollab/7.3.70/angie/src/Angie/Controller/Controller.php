<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Controller;

use Angie\Controller\Error\ActionNotFound;
use Angie\Http\Request;
use Angie\Inflector;
use ReflectionClass;
use ReflectionMethod;
use User;

/**
 * Controller implementation.
 *
 * @package angie.library.controller
 */
abstract class Controller implements ControllerInterface
{
    /**
     * @param  User|null $user
     * @return mixed
     */
    protected function __before(Request $request, $user)
    {
        return null;
    }

    public function executeAction($action, Request $request)
    {
        if (!in_array($action, $this->getActions())) {
            throw new ActionNotFound($this->getName(), $action);
        }

        $before_result = $this->__before($request, $request->getAttribute('authenticated_user'));

        if ($before_result) {
            return $before_result;
        }

        return $this->$action($request, $request->getAttribute('authenticated_user'));
    }

    /**
     * Return controller name based on controller class name.
     *
     * @param  string|null $controller_class_name
     * @return string
     */
    public function getName($controller_class_name = null)
    {
        if (empty($controller_class_name)) {
            $controller_class_name = get_class($this);
        }

        return Inflector::underscore(substr($controller_class_name, 0, strlen($controller_class_name) - 10));
    }

    /**
     * Cached array of actions.
     *
     * @param array
     */
    private $actions = false;

    /**
     * Returns array of controller actions.
     */
    private function getActions()
    {
        if ($this->actions === false) {
            $this->actions = [];

            $reflection = new ReflectionClass($this);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (substr($method->getName(), 0, 2) !== '__' && $method->getDeclaringClass()->getName() !== 'Controller') {
                    $this->actions[] = $method->getName(); // Filter only methods that are defined in this class
                }
            }
        }

        return $this->actions;
    }
}
