<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use Angie\Events;
use Angie\Mailer;

class EmailNotificationChannel extends NotificationChannel
{
    const CHANNEL_NAME = 'email';

    public function getShortName(): string
    {
        return self::CHANNEL_NAME;
    }

    /**
     * Return verbose name of the channel.
     *
     * @return string
     */
    public function getVerboseName()
    {
        return lang('Email Notifications');
    }

    /**
     * Returns true if this channel is enabled by default.
     *
     * @return bool
     */
    public function isEnabledByDefault()
    {
        return true;
    }

    /**
     * Returns true if this channel is enabled for this user.
     *
     * @return bool
     */
    public function isEnabledFor(User $user)
    {
        return true;
    }

    /**
     * Send notification via this channel.
     */
    public function send(Notification &$notification, IUser $recipient, bool $skip_sending_queue = false)
    {
        $template = $this->getTemplateForNotification($notification);
        $template->assign(
            [
                'recipient' => $recipient,
                'language' => $recipient->getLanguage(),
                'context_view_url' => $this->getParentViewUrlForUser($notification, $recipient),
            ]
        );
        $content = $template->fetch();

        if (strpos($content, '================================================================================')) {
            [
                $subject,
                $body,
            ] = explode(
                '================================================================================',
                $content
            );

            $subject = undo_htmlspecialchars(trim($subject)); // Subject does not have to be escaped
            $body = trim($body);
        } else {
            $subject = lang('[No Subject]', null, true, $recipient->getLanguage());
            $body = trim($content);
        }

        Mailer::send(
            $notification->getSender(),
            $recipient,
            $subject,
            $body,
            [
                'context' => $notification->getParent(),
                'attachments' => $notification->getAttachments($this),
                'decorator' => $notification->getDecorator(),
                'unsubscribe_url' => $notification->getUnsubscribeUrl($recipient),
                'unsubscribe_label' => $notification->getUnsubscribeLabel($recipient),
                'supports_go_to_action' => $notification->supportsGoToAction($recipient),
            ]
        );
    }

    /**
     * Return parent view URL for given user.
     */
    private function getParentViewUrlForUser(Notification $notification, IUser $user): ?string
    {
        $parent = $notification->getParent();

        if ($parent instanceof ApplicationObject) {
            $default_view_url = AngieApplication::cache()->getByObject(
                $parent,
                'default_notification_view_url',
                function () use ($parent) {
                    return $parent instanceof RoutingContextInterface ? $parent->getViewUrl() : null;
                }
            );

            return AngieApplication::cache()->getByObject(
                $parent,
                [
                    'notification_view_url',
                    $user->getEmail(),
                ],
                function () use ($user, $parent, $default_view_url) {
                    $context_view_url = $default_view_url;

                    Events::trigger(
                        'on_notification_context_view_url',
                        [
                            &$user,
                            &$parent,
                            &$context_view_url,
                        ]
                    );

                    return $context_view_url;
                }
            );
        }

        return null;
    }

    /**
     * Cached template instances.
     *
     * @var Smarty_Internal_Template[]
     */
    private array $templates = [];

    /**
     * @var string[]
     */
    private array $template_paths = [];

    /**
     * Return template for a particular notification.
     *
     * @return Smarty_Internal_Template
     */
    private function &getTemplateForNotification(Notification &$notification)
    {
        $key = get_class($notification) . '-' . $notification->getId();

        if (empty($this->templates[$key])) {
            $this->template_paths[$key] = $notification->getTemplatePath($this);
            $this->templates[$key] = AngieApplication::getSmarty()->createTemplate($this->template_paths[$key]);
        }

        // ---------------------------------------------------
        //  Assign variables. Can't be part of the above IF
        //  because we might be sending multiple instances of
        //  the same class, that have different parameters
        // ---------------------------------------------------

        $sender = $notification->getSender();

        if (!$sender instanceof IUser) {
            $sender = Mailer::getDefaultSender();
        }

        $context = $notification->getParent();
        $additional_template_vars = $notification->getAdditionalTemplateVars($this);

        $this->templates[$key]->assignByRef('sender', $sender);
        $this->templates[$key]->assignByRef('context', $context);
        $this->templates[$key]->assign($additional_template_vars);

        AngieApplication::log()->debug(
            'Email template variables for {notification} notification prepared',
            [
                'notification' => get_class($notification),
                'template_path' => substr($this->template_paths[$key], strlen(APPLICATION_PATH)),
                'vars' => $this->getTemplateVarsForLog($sender, $context, $additional_template_vars),
            ]
        );

        return $this->templates[$key];
    }

    private function getTemplateVarsForLog(IUser $sender, $context, array $additional): array
    {
        $result = [
            'sender' => $sender instanceof AnonymousUser ? $this->serializeTemplateVarForLog($sender) : null,
        ];

        foreach (array_merge(['context' => $context], $additional) as $k => $v) {
            $result[$k] = $this->serializeTemplateVarForLog($v, true);
        }

        return $result;
    }

    /**
     * Serialize template variable for log.
     *
     * @param  mixed        $value
     * @return array|string
     */
    private function serializeTemplateVarForLog($value, bool $follow_array = false)
    {
        if ($value instanceof User) {
            return "User #{$value->getId()} <{$value->getEmail()}>";
        } elseif ($value instanceof AnonymousUser) {
            return $value->getName() ? "{$value->getName()} <{$value->getEmail()}>" : "<{$value->getEmail()}>";
        }

        if ($value instanceof DataObject) {
            return get_class($value) . ' #' . $value->getId();
        } elseif (is_object($value)) {
            return get_class($value);
        } elseif (is_scalar($value) || is_null($value)) {
            return $value;
        } elseif (is_array($value)) {
            if ($follow_array) {
                $result = [];

                foreach ($value as $k => $v) {
                    $result[$k] = $this->serializeTemplateVarForLog($v, false);
                }

                return $result;
            }

            return '[array]';
        }

        return '--';
    }
}
