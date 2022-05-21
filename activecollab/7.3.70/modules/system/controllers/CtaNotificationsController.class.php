<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Inflector;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class CtaNotificationsController extends AuthRequiredController
{
    /**
     * @var CtaNotificationInterface
     */
    private $active_notification;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $notification_type = $this->getNotificationTypeFromRequest($request);

        if ($this->isValidateNotificationType($notification_type)) {
            $this->active_notification = AngieApplication::CTANotifications()->loadNotification($notification_type);

            if($user->getId() !== Users::findFirstOwner()->getId()) {
                return Response::NOT_FOUND;
            }
        } else {
            return Response::NOT_FOUND;
        }

        return null;
    }

    public function show()
    {
        return $this->active_notification;
    }

    public function dismiss()
    {
        if ($this->active_notification->dismiss()) {
            return [
                'is_ok' => true,
            ];
        }

        return Response::BAD_REQUEST;
    }

    private function getNotificationTypeFromRequest(Request $request): string
    {
        $notification_type = trim($request->get('notification_type'));

        if ($notification_type) {
            return Inflector::camelize(str_replace('-', '_', $notification_type)) . 'Notification';
        }

        return '';
    }

    private function isValidateNotificationType(string $notification_type): bool
    {
        if (empty($notification_type)) {
            return false;
        }

        if (!class_exists($notification_type, true)) {
            return false;
        }

        return (new ReflectionClass($notification_type))->implementsInterface(CTANotificationInterface::class);
    }
}
