<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Notifications\TemplatePathResolver;

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannelInterface;
use ActiveCollab\Foundation\Notifications\NotificationInterface;
use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use FileDnxError;
use ReflectionClass;

class NotificationTemplatePathResolver implements NotificationTemplatePathResolverInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getNotificationTemplatePath(
        NotificationInterface $notification,
        NotificationChannelInterface $channel
    ): string
    {
        $notification_class = get_class($notification);
        $channel_name = $channel->getShortName();

        return (string) $this->cache->get(
            [
                'notification_template_paths',
                $notification_class,
                $channel_name,
            ],
            function () use ($notification, $notification_class, $channel_name) {
                $class = new ReflectionClass($notification_class);

                $potential_paths = [
                    $this->getTemplatePathFromNotificationClass(
                        $class,
                        $channel_name,
                        $notification
                    ),
                    $this->getTemplatePathFromNotificationClass(
                        $class->getParentClass(),
                        $channel_name,
                        $notification
                    ),
                ];

                foreach ($potential_paths as $potential_path) {
                    if (is_file($potential_path)) {
                        return $potential_path;
                    }
                }

                throw new FileDnxError($potential_paths[0]);
            }
        );
    }

    private function getTemplatePathFromNotificationClass(
        ReflectionClass $class,
        string $channel_name,
        NotificationInterface $notification
    ): string
    {
        return sprintf(
            '%s/%s/%s.tpl',
            dirname($class->getFileName()),
            $channel_name,
            $notification->getShortName(),
        );
    }
}
