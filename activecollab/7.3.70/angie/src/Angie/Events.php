<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use AngieApplication;
use Closure;

/**
 * Events manager.
 *
 * @package angie.library.events
 */
final class Events
{
    /**
     * Array of event definitions.
     *
     * @var array
     */
    private static $handlers = [];

    /**
     * Subscribe $callback function to $event.
     *
     * @param string         $event
     * @param Closure|string $callback
     */
    public static function listen($event, $callback)
    {
        if (is_string($callback)) {
            [$module, $callback] = explode('/', $callback);

            $callback_function_name = $module . '_handle_' . $callback;

            if (isset(self::$handlers[$event])) {
                foreach (self::$handlers[$event] as $subscribed_handler) {
                    if (is_array($subscribed_handler) && $subscribed_handler[0] == $callback_function_name) {
                        return; // Handler already subscribed
                    }
                }
            }

            $handler_file = AngieApplication::getEventHandlerPath($callback, $module);

            $callback = [$callback_function_name, $handler_file];
        }

        if (empty(self::$handlers[$event])) {
            self::$handlers[$event] = [];
        }

        self::$handlers[$event][] = $callback;
    }

    /**
     * Trigger specific event with a given parameters.
     *
     * @param string $event
     * @param array  $params
     */
    public static function trigger($event, $params = [])
    {
        if (!empty(self::$handlers[$event])) {
            foreach (self::$handlers[$event] as $handler) {
                if ($handler instanceof Closure) {
                    $callback_result = call_user_func_array($handler, $params);
                } else {
                    [$callback, $location] = $handler; // Extract callback function name and expected location

                    if (!function_exists($callback)) {
                        require_once $location;
                    }

                    $callback_result = call_user_func_array($callback, $params);
                }

                if ($callback_result === false) {
                    break;
                }
            }
        }
    }
}
