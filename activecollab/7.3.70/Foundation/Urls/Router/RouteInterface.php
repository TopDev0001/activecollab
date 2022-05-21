<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router;

interface RouteInterface
{
    // Config
    const REGEX_DELIMITER = '#';
    const URL_VARIABLE = ':';

    /**
     * Return regular expresion that will match path part of the URL.
     *
     * @return string
     */
    public function getRegularExpression();

    /**
     * Return named parameters.
     *
     * @return array
     */
    public function getNamedParameters();

    /**
     * Get name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set name value.
     *
     * @param string $value
     */
    public function setName($value);

    /**
     * Get route_string.
     *
     * @return string
     */
    public function getRouteString();

    /**
     * Set route_string value.
     *
     * @param string $value
     */
    public function setRouteString($value);

    /**
     * Return defaults value.
     *
     * @return array
     */
    public function getDefaults();

    /**
     * Return requirements value.
     *
     * @return array
     */
    public function getRequirements();

    /**
     * Returns true if this route has a variable with a given name.
     *
     * @param  string $variable_name
     * @return bool
     */
    public function hasVariable($variable_name);
}
