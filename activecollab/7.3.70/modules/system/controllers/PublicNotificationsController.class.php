<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_not_required', EnvironmentFramework::INJECT_INTO);

class PublicNotificationsController extends AuthNotRequiredController
{
    public function subscribe(Request $request)
    {
        if ($code = $request->get('code')) {
            $parts = explode('-', strtoupper($code));

            if (count($parts) > 1) {
                $notification = array_shift($parts);

                $subscribed = null;
                $subscribed_message = '';
                $undo_code = '';

                Angie\Events::trigger('on_handle_public_subscribe', [$notification, $parts, &$subscribed, &$subscribed_message, &$undo_code]);

                if ($subscribed !== null && $subscribed_message) {
                    return [
                        'subscribed' => $subscribed,
                        'subscribed_message' => $subscribed_message,
                        'undo_code' => $undo_code,
                        'undo_url' => $undo_code
                            ? AngieApplication::getContainer()
                                ->get(RouterInterface::class)
                                    ->assemble(
                                        'public_notifications_subscribe',
                                        [
                                            'code' => $undo_code,
                                        ]
                                    )
                            : '',
                    ];
                } else {
                    return Response::NOT_FOUND;
                }
            } else {
                return Response::BAD_REQUEST;
            }
        } else {
            return Response::BAD_REQUEST;
        }
    }

    public function unsubscribe(Request $request)
    {
        if ($code = $request->get('code')) {
            $parts = explode('-', strtoupper($code));

            if (count($parts) > 1) {
                $notification = array_shift($parts);

                $unsubscribed = null;
                $unsubscribed_message = '';
                $undo_code = '';

                Angie\Events::trigger('on_handle_public_unsubscribe', [$notification, $parts, &$unsubscribed, &$unsubscribed_message, &$undo_code]);

                if ($unsubscribed !== null && $unsubscribed_message) {
                    return [
                        'unsubscribed' => $unsubscribed,
                        'unsubscribed_message' => $unsubscribed_message,
                        'undo_code' => $undo_code,
                        'undo_url' => $undo_code
                            ? AngieApplication::getContainer()
                                ->get(RouterInterface::class)
                                    ->assemble(
                                        'public_notifications_subscribe',
                                        [
                                            'code' => $undo_code,
                                        ]
                                    )
                            : '',
                    ];
                } else {
                    return Response::NOT_FOUND;
                }
            } else {
                return Response::BAD_REQUEST;
            }
        } else {
            return Response::BAD_REQUEST;
        }
    }
}
