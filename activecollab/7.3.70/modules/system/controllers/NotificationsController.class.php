<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationServiceInterface;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', NotificationsFramework::INJECT_INTO);

class NotificationsController extends AuthRequiredController
{
    public function index(Request $request, User $user)
    {
        return Users::prepareCollection('notifications_for_recipient_' . $user->getId(), $user);
    }

    public function unread(Request $request, User $user)
    {
        return Users::prepareCollection('unread_notifications_for_recipient_' . $user->getId(), $user);
    }
    public function object_updates_unread_count(Request $request, User $user)
    {
        /** @var UserObjectUpdatesCollection $collection */
        $collection = Users::prepareCollection(
            'recent_object_updates_for_recipient_' . $user->getId(),
            $user);

        return [
            'is_ok' => true,
            'count' => $collection->countUnread(),
        ];
    }

    public function object_updates(Request $request, User $user)
    {
        return Users::prepareCollection(
            'object_updates_for_recipient_' . $user->getId() . '_page_' . $request->getPage(),
            $user
        );
    }

    public function recent_object_updates(Request $request, User $user)
    {
        return Users::prepareCollection('recent_object_updates_for_recipient_' . $user->getId(), $user);
    }

    public function mark_all_as_read(Request $request, User $user)
    {
        return Notifications::updateReadStatusForRecipient($user, true);
    }

    public function notification_object_updates(Request $request, User $user)
    {
        return Notifications::getRecentUpdate($user, $request->get('notification_id'));
    }

    public function push_notification_subscribe(Request $request, User $user) {
        $valid = $request->post('unique_key') && $request->post('token');
        if(!$valid){
            return Response::BAD_REQUEST;
        }
        AngieApplication::getContainer()
            ->get(PushNotificationServiceInterface::class)
            ->subscribe($user, $request->post());

        return Response::CREATED;
    }

    public function push_notification_unsubscribe(Request $request, User $user) {
        $unique_id = $request->put('unique_key');
        if(!$unique_id){
            return Response::BAD_REQUEST;
        }
        AngieApplication::getContainer()
            ->get(PushNotificationServiceInterface::class)
            ->unsubscribe($user, $request->put('unique_key'));

        return Response::NO_CONTENT;
    }
}
