<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Authentication\Token\TokenInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Psr\Log\LogLevel;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Application level logger controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class LoggerController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        // Token based requests are forbidden
        if ($request->getAttribute('authenticated_with') instanceof TokenInterface) {
            return Response::FORBIDDEN;
        }
    }

    /**
     * @return int
     */
    public function add(Request $request, User $user)
    {
        $level = $request->get('log_level');

        $message = $request->post('message', 'Unknown message');
        $context = $request->post('context', []);

        $available_log_levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];

        if (!in_array($level, $available_log_levels)) {
            AngieApplication::log()->error(
                "Failed to add '{level}' level log.",
                [
                    'level' => $level,
                ]
            );

            return Response::BAD_REQUEST;
        }

        if (!is_array($context)) {
            AngieApplication::log()->error('Failed to add new log. Context value must be array.');

            return Response::BAD_REQUEST;
        }

        $context['facility'] = 'frontend';

        AngieApplication::log()->log($level, $message, $context);

        return Response::OK;
    }
}
