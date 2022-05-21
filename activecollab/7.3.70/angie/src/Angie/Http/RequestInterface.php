<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http;

use Psr\Http\Message\ServerRequestInterface;

interface RequestInterface extends ServerRequestInterface
{
    /**
     * Set required metadata for further request dispatch.
     *
     * @param  string $module
     * @param  string $controller
     * @param  string $action
     * @return $this
     */
    public function setRequestMetadata($module, $controller, $action);

    /**
     * Return name of the module that needs to serve this request.
     *
     * @return string
     */
    public function getModule();

    /**
     * Return requested controller.
     *
     * @return string
     */
    public function getController();

    /**
     * Return requested action.
     *
     * @return string
     */
    public function getAction();

    /**
     * Return action name for the given method.
     *
     * @param  string $method
     * @return string
     */
    public function getActionForMethod($method);

    /**
     * Return variable from GET.
     *
     * If $var is NULL, entire GET array will be returned
     *
     * @param  string $var
     * @param  mixed  $default
     * @return mixed
     */
    public function get($var = null, $default = null);

    public function getId($name = 'id', array $from = null, int $default = null): int;
    public function getPage(string $variable_name = 'page'): int;

    /**
     * Return POST variable.
     *
     * If $var is NULL, entire POST array will be returned
     *
     * @param  string      $var
     * @param  mixed       $default
     * @return array|mixed
     */
    public function post($var = null, $default = null);

    /**
     * Return PUT value.
     *
     * @param  string $var
     * @param  mixed  $default
     * @return mixed
     */
    public function put($var = null, $default = null);

    /**
     * @param  string      $key
     * @return string|null
     */
    public function getServerParam($key);

    /**
     * @return string
     */
    public function getQueryString();
}
