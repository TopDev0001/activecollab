<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use IUser;
use Notification;
use User;

class PushNotificationService implements PushNotificationServiceInterface
{
    private UserDeviceManagerInterface $manager;
    private PushNotificationPayloadTransformerInterface $transformer;

    public function __construct(
        UserDeviceManagerInterface $manager,
        PushNotificationPayloadTransformerInterface $transformer
    ) {
        $this->manager = $manager;
        $this->transformer = $transformer;
    }

    public function subscribe(User $user, array $payload): void
    {
        if ($device = $this->manager->findDeviceByUserIdAndUniqueKey($user->getId(), $payload['unique_key'])) {
            $device->setToken($payload['token']);
            $device->save();
        } else {
            $this->manager->createDeviceForUser($user, $payload);
        }
    }

    public function unsubscribe(User $user, string $unique_key): void
    {
        if ($device = $this->manager->findDeviceByUserIdAndUniqueKey($user->getId(), $unique_key)) {
            $device->delete();
        }
    }

    public function getManager(): UserDeviceManagerInterface
    {
        return $this->manager;
    }

    public function getPayload(Notification $notification, IUser $recipient): array
    {
        return $this->transformer->transform($notification, $recipient);
    }
}
