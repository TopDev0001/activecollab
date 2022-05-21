<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Foundation\Urls\Router;

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;

class Route implements RouteInterface
{
    /**
     * Name of the route.
     *
     * @var string
     */
    private $name;

    /**
     * Input route string that is parsed into parts on construction.
     *
     * @var string
     */
    private $route_string;

    /**
     * ActiveCollab\Foundation\Urls\Router\Route string parsed into associative array of param name => regular
     * expression.
     *
     * @var array
     */
    private $parts;

    /**
     * Default values for specific params.
     *
     * @var array
     */
    private $defaults = [];

    /**
     * Regular expressions that force specific expressions for specific params.
     *
     * @var array
     */
    private $requirements = [];

    /**
     * Cached array of variables.
     *
     * @var array
     */
    private $variables = [];
    /**
     * Cached regular expression that will match this route.
     *
     * @var string
     */
    private $regex = false; // __construct

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Construct route.
     *
     * This function will parse route string and populate $this->parts with rules
     * that need to be matched
     *
     * @param string $name
     * @param string $route
     * @param array  $defaults
     * @param array  $requirements
     */
    public function __construct($name, $route, $defaults = [], $requirements = [])
    {
        $this->route_string = $route; // original string

        $route = trim($route, '/');

        $this->name = $name;
        $this->defaults = (array) $defaults;
        $this->requirements = (array) $requirements;

        foreach (explode('/', $route) as $pos => $part) {
            if (substr($part, 0, 1) == self::URL_VARIABLE) {
                $name = substr($part, 1);
                $regex = (isset($requirements[$name]) ? '(' . $requirements[$name] . ')' : '(' . UrlMatcherInterface::MATCH_SLUG . ')');
                $this->parts[$pos] = [
                    'name' => $name,
                    'regex' => $regex,
                ]; // array

                $this->variables[] = $name;
            } else {
                $this->parts[$pos] = [
                    'raw' => $part,
                    'regex' => str_replace('\-', '-', preg_quote($part, self::REGEX_DELIMITER)), // Unescape \-
                ]; // array
            }
        }
    }

    /**
     * Return regular expresion that will match path part of the URL.
     *
     * @return string
     */
    public function getRegularExpression()
    {
        if ($this->regex === false) {
            $this->regex = [];
            foreach ($this->parts as $part) {
                $this->regex[] = $part['regex'];
            }

            $this->regex = '/^' . implode('\/', $this->regex) . '$/';
        }

        return $this->regex;
    }

    /**
     * Return named parameters.
     *
     * @return array
     */
    public function getNamedParameters()
    {
        $parameters = [];
        foreach ($this->parts as $part) {
            if (isset($part['name'])) {
                $parameters[] = $part['name'];
            }
        }

        return $parameters;
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name value.
     *
     * @param string $value
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * Get route_string.
     *
     * @return string
     */
    public function getRouteString()
    {
        return $this->route_string;
    }

    /**
     * Set route_string value.
     *
     * @param string $value
     */
    public function setRouteString($value)
    {
        $this->route_string = $value;
    }

    /**
     * Return defaults value.
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Return requirements value.
     *
     * @return array
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Returns true if this route has a variable with a given name.
     *
     * @param  string $variable_name
     * @return bool
     */
    public function hasVariable($variable_name)
    {
        return in_array($variable_name, $this->variables);
    }
}
