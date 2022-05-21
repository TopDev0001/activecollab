<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use Angie\Controller\Controller;
use Angie\Controller\Error\ActionNotFound;
use Angie\Controller\Error\ControllerNotFound;
use Angie\Http\Encoder\EncoderInterface;
use Angie\Http\Response\StatusResponse\InternalServerErrorStatusResponse;
use Angie\Inflector;
use Angie\Middleware\Base\EncoderMiddleware;
use FileDnxError;
use ImpossibleCollectionError;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @package Angie\Middleware
 */
class ControllerActionMiddleware extends EncoderMiddleware
{
    /**
     * @var callable
     */
    private $controller_file_resolver;

    public function __construct(callable $controller_file_resolver, EncoderInterface $encoder, LoggerInterface $logger = null)
    {
        parent::__construct($encoder, $logger);

        $this->controller_file_resolver = $controller_file_resolver;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $module_name = $request->getAttribute('module');
        $controller_name = $request->getAttribute('controller');
        $action_name = $request->getAttribute('action');

        if (!$module_name || !$controller_name || !$action_name) {
            throw new LogicException('Request not routed.');
        }

        $action_result = null;

        try {
            $controller = $this->getControllerInstance($module_name, $controller_name);
            $action_result = $controller->executeAction($action_name, $request);
        } catch (ActionNotFound $e) {
            if ($this->getLogger()) {
                $this->getLogger()->error('Action {action} not found in {controller}', [
                    'action' => $action_name,
                    'controller' => $controller_name,
                    'module' => $module_name,
                    'exception' => $e,
                ]);
            }

            return $this->getEncoder()->encode(new InternalServerErrorStatusResponse('Action not found.'), $request, $response)[1];
        } catch (ControllerNotFound $e) {
            if ($this->getLogger()) {
                $this->getLogger()->error('Controller class for {controller} not found in {module}', [
                    'controller' => $controller_name,
                    'module' => $module_name,
                    'exception' => $e,
                ]);
            }

            return $this->getEncoder()->encode(new InternalServerErrorStatusResponse('Controller not found.'), $request, $response)[1];
        } catch (FileDnxError $e) {
            if ($this->getLogger()) {
                $this->getLogger()->error('Controller file for {controller} not found in {module}', [
                    'controller' => $controller_name,
                    'module' => $module_name,
                    'exception' => $e,
                ]);
            }

            return $this->getEncoder()->encode(new InternalServerErrorStatusResponse('Controller not found.'), $request, $response)[1];
        } catch (ImpossibleCollectionError $e) {
            if ($this->getLogger()) {
                $this->getLogger()->notice(
                    'Controller action threw ImpossibleCollectionException',
                    [
                        'exception' => $e,
                    ]
                );
            }

            $action_result = [];
        }

        $request = $request->withAttribute(self::ACTION_RESULT_ATTRIBUTE, $action_result);

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * @param  string             $module_name
     * @param  string             $controller_name
     * @return Controller
     * @throws FileDnxError
     * @throws ControllerNotFound
     */
    private function getControllerInstance($module_name, $controller_name)
    {
        call_user_func($this->controller_file_resolver, $module_name, $controller_name);

        $controller_class_name = Inflector::camelize($controller_name) . 'Controller';
        if (!class_exists($controller_class_name, false)) {
            throw new ControllerNotFound($controller_name);
        }

        $controller_class_reflection = new ReflectionClass($controller_class_name);
        if (!$controller_class_reflection->isSubclassOf(Controller::class)) {
            throw new ControllerNotFound($controller_name);
        }

        return new $controller_class_name();
    }
}
