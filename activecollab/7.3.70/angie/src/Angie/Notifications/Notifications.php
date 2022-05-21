<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Notifications;

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Foundation\Notifications\NotificationInterface;
use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationScheduleMatcherInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationServiceInterface;
use Angie\Error;
use Angie\Inflector;
use Angie\Mailer\Decorator\Decorator;
use Angie\Modules\AngieFramework;
use AngieApplication;
use ApplicationObject;
use ClassNotImplementedError;
use EmailNotificationChannel;
use Exception;
use FileDnxError;
use InvalidInstanceError;
use InvalidParamError;
use IUser;
use LogicException;
use Notification;
use PushNotificationChannel;
use RealTimeNotificationChannel;
use ReflectionClass;
use WebInterfaceNotificationChannel;

class Notifications implements NotificationsInterface
{
    public function createNotification(
        string $notification_class,
        ApplicationObject $context = null,
        IUser $sender = null,
        Decorator $decorator = null
    ): NotificationInterface
    {
        $notification = new $notification_class();

        if (!$notification instanceof NotificationInterface || !$notification instanceof Notification) {
            throw new LogicException('Notification class does not implement notification interface.');
        }

        if ($context) {
            $notification->setParent($context);
        }

        if ($sender) {
            $notification->setSender($sender);
        }

        if ($decorator instanceof Decorator) {
            $notification->setDecorator($decorator);
        }

        return $notification;
    }

    public function notifyAbout(
        string $event,
        ApplicationObject $context = null,
        IUser $sender = null,
        Decorator $decorator = null
    ): NotificationInterface
    {
        $notification = $this->eventToNotificationInstance($event);

        if ($context) {
            $notification->setParent($context);
        }

        if ($sender) {
            $notification->setSender($sender);
        }

        if ($decorator instanceof Decorator) {
            $notification->setDecorator($decorator);
        }

        return $notification;
    }

    public function getNotificationTemplatePath(
        Notification $notification,
        NotificationChannel $channel
    ): string
    {
        $notification_class = get_class($notification);
        $channel_name = $channel->getShortName();

        return (string) AngieApplication::cache()->get(
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

    private function eventToNotificationInstance(string $event): Notification
    {
        if (strpos($event, '/') === false) {
            $module_name = SystemModule::NAME;
            $event_name = $event;
        } else {
            [$module_name, $event_name] = explode('/', $event);
        }

        $module = AngieApplication::getModule($module_name);

        if (!$module instanceof AngieFramework) {
            throw new InvalidParamError(
                'event',
                $event,
                sprintf(
                    "Invalid module name found in '%s' event",
                    $event
                )
            );
        }

        $notification_class_name = Inflector::camelize($event_name) . 'Notification';
        $notification_class_path = sprintf(
            '%s/notifications/%s.class.php',
            $module->getPath(),
            $notification_class_name
        );

        if (!class_exists($notification_class_name, false)) {
            if (!is_file($notification_class_path)) {
                throw new FileDnxError(
                    $notification_class_path,
                    sprintf(
                        "Failed to load notification class for '%s' event",
                        $event
                    )
                );
            }

            require_once $notification_class_path;

            if (!class_exists($notification_class_name, false)) {
                throw new ClassNotImplementedError($notification_class_name, $notification_class_path);
            }
        }

        $notification = new $notification_class_name();

        if ($notification instanceof Notification) {
            return $notification;
        }

        throw new ClassNotImplementedError(
            $notification_class_name,
            $notification_class_path,
            sprintf(
                "Class '%s' found, but it does not inherit Notification class",
                $notification_class_name
            )
        );
    }

    // ---------------------------------------------------
    //  Channels and Sending
    // ---------------------------------------------------

    /**
     * Send $notification to the list of recipients.
     *
     * @param IUser[] $users
     */
    public function sendNotificationToRecipients(
        Notification &$notification,
        $users,
        bool $skip_sending_queue = false
    ): void
    {
        if ($users instanceof IUser) {
            $users = [
                $users,
            ];
        }

        if (empty($users) || !is_foreachable($users)) {
            return;
        }

        if ($notification->isNew()) {
            $notification->save();
        }

        $recipients = [];

        // Check recipients list
        foreach ($users as $user) {
            if ($user instanceof IUser) {
                if (isset($recipients[$user->getEmail()])) {
                    continue;
                }

                if (!$notification->isThisNotificationVisibleToUser($user) || $notification->isUserBlockingThisNotification($user)) {
                    continue; // Remove from list of recipients if user can't see this notification, or if user is blocking it
                }

                $recipients[$user->getEmail()] = $user;
            } else {
                throw new InvalidInstanceError('user', $user, IUser::class);
            }
        }

        if (count($recipients)) {
            try {
                $this->openChannels();

                foreach ($recipients as $recipient) {
                    foreach ($this->getChannels() as $channel) {
                        if ($notification->isThisNotificationVisibleInChannel($channel, $recipient)) {
                            $channel->send($notification, $recipient, $skip_sending_queue);
                        }
                    }
                }

                $this->closeChannels();
            } catch (Exception $e) {
                $this->closeChannels(true);
                throw $e;
            }
        }
    }

    /**
     * Array of registered notification channels.
     *
     * @var NotificationChannel[]
     */
    private $channels = false;

    /**
     * Return notification channels.
     *
     * @return NotificationChannel[]
     */
    public function &getChannels()
    {
        if ($this->channels === false) {
            $this->channels = [
                new WebInterfaceNotificationChannel(),
                new RealTimeNotificationChannel(),
                new PushNotificationChannel(
                    AngieApplication::getContainer()->get(PushNotificationServiceInterface::class),
                    AngieApplication::getContainer()->get(PushNotificationScheduleMatcherInterface::class)
                ),
                new EmailNotificationChannel(),
            ];
        }

        return $this->channels;
    }

    /**
     * Indicate whether channels are open.
     *
     * @var bool
     */
    private $channels_are_open = false;

    /**
     * Returns true if channels are open.
     *
     * @return bool
     */
    public function channelsAreOpen()
    {
        return $this->channels_are_open;
    }

    /**
     * Open notifications channels for bulk sending.
     */
    public function openChannels()
    {
        if ($this->channels_are_open) {
            throw new Error('Channels are already open');
        }

        foreach ($this->getChannels() as $channel) {
            $channel->open();
        }

        $this->channels_are_open = true;
    }

    /**
     * Close notification channels for bulk sending.
     *
     * @param bool $sending_interupted
     */
    public function closeChannels($sending_interupted = false)
    {
        if (empty($this->channels_are_open) && empty($sending_interupted)) {
            throw new Error('Channels are not open');
        }

        for ($i = count($this->channels) - 1; $i >= 0; --$i) {
            $this->channels[$i]->close($sending_interupted);
        }

        $this->channels_are_open = false;
    }
}
