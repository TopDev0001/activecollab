<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class SystemNotificationsController extends AuthRequiredController
{
    protected $active_notification;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_notification = DataObjectPool::get(
            SystemNotification::class,
            $request->getId('notification_id')
        );

        return null;
    }

    public function index(Request $request, User $user)
    {
        return SystemNotifications::prepareCollection(SystemNotifications::ALL, $user);
    }

    public function dismiss(Request $request, User $user)
    {
        return $this->active_notification instanceof SystemNotification && $this->active_notification->isLoaded() && $this->active_notification->canDismiss($user)
            ? $this->active_notification->dismiss()
            : Response::NOT_FOUND;
    }
}
