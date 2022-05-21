<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use Angie\Mailer\Adapter\Adapter;
use Angie\Mailer\Adapter\Queued;
use Angie\Mailer\Adapter\Silent;
use Angie\Mailer\Decorator\Decorator;
use Angie\Mailer\Decorator\Generic;
use AngieApplication;
use AnonymousUser;
use DataObject;
use EmailFramework;
use EmailIntegration;
use Integrations;
use InvalidParamError;
use IUser;
use User;

final class Mailer
{
    /**
     * Mailing adapter.
     *
     * @var Adapter
     */
    private static $adapter;

    /**
     * Return mailer adapter.
     *
     * @return Adapter
     */
    public static function getAdapter()
    {
        if (empty(self::$adapter)) {
            switch (self::getConnectionType()) {
                case EmailFramework::MAILING_SILENT:
                    self::$adapter = new Silent();
                    break;
                case EmailFramework::MAILING_QUEUED:
                    self::$adapter = new Queued();
                    break;
                default:
                    throw new InvalidParamError(
                        'mailing',
                        self::getConnectionType(),
                        'Invalid mailer type'
                    );
            }
        }

        return self::$adapter;
    }

    public static function setAdapter(Adapter $adapter)
    {
        self::$adapter = $adapter;
    }

    /**
     * Mailer decorator.
     *
     * @var Decorator
     */
    private static $decorator;

    /**
     * Return mailer decorator.
     *
     * @return Decorator
     */
    public static function getDecorator()
    {
        if (empty(self::$decorator)) {
            self::$decorator = new Generic();
        }

        return self::$decorator;
    }

    public static function setDecorator(Decorator $decorator)
    {
        self::$decorator = $decorator;
    }

    /**
     * Default sender instance.
     *
     * @var AnonymousUser
     */
    private static $default_sender;

    /**
     * Return default sender.
     *
     * @return IUser
     */
    public static function getDefaultSender()
    {
        if (empty(self::$default_sender)) {
            [
                $from_email,
                $from_name,
            ] = self::getFromEmailAndName();

            if ($from_email) {
                self::setDefaultSender(new AnonymousUser($from_name, $from_email));
            } else {
                self::setDefaultSender(new AnonymousUser($from_name, ADMIN_EMAIL));
            }
        }

        return self::$default_sender;
    }

    /**
     * Set default from user.
     */
    public static function setDefaultSender(AnonymousUser $sender = null)
    {
        self::$default_sender = $sender;
    }

    // ---------------------------------------------------
    //  Send Message
    // ---------------------------------------------------

    /**
     * Send a message to one or more recipients.
     *
     * Supported additional parameters:
     *
     * - context - Context in which notification is sent
     * - decorate - Whether email should be decorated or not. This parameter is
     *   taken into account only if message is sent instantly. Default is TRUE
     *
     * @param IUser[]|IUser $recipients
     * @param string        $subject
     * @param string        $body
     * @param array         $additional
     */
    public static function send(
        ?IUser $sender,
        $recipients,
        $subject = '',
        $body = '',
        $additional = null
    ): void
    {
        if (empty($sender)) {
            $sender = self::getDefaultSender();
        }

        if ($recipients instanceof IUser) {
            self::sendToRecipient($sender, $recipients, $subject, $body, $additional);
        } elseif (is_array($recipients)) {
            foreach ($recipients as $recipient) {
                self::sendToRecipient($sender, $recipient, $subject, $body, $additional);
            }
        } else {
            throw new InvalidParamError(
                'recipients',
                $recipients,
                'Recipient should be an IUser instance or a list of users'
            );
        }
    }

    /**
     * Send message to a single recipient.
     *
     * @param string     $subject
     * @param string     $body
     * @param array|null $additional
     */
    private static function sendToRecipient(
        IUser $sender,
        IUser $recipient,
        $subject,
        $body,
        $additional = null
    ): void
    {
        // ignore if user is not active or his email is @example.com
        if (($recipient instanceof User && !$recipient->isActive())
            || strpos($recipient->getEmail(), '@example.com') !== false
        ) {
            return;
        }

        /** @var DataObject $context */
        $context = isset($additional['context']) && $additional['context'] instanceof DataObject ? $additional['context'] : null;

        /** @var Decorator $decorator */
        $decorator = isset($additional['decorator']) && $additional['decorator'] instanceof Decorator ? $additional['decorator'] : self::getDecorator();

        $unsubscribe_url = !empty($additional['unsubscribe_url']) ? $additional['unsubscribe_url'] : '';
        $unsubscribe_label = !empty($additional['unsubscribe_label']) ? $additional['unsubscribe_label'] : '';
        $supports_go_to_action = isset($additional['supports_go_to_action']) && $additional['supports_go_to_action'];

        $subject = $decorator->decorateSubject($subject);
        $body = $decorator->decorateBody(
            $recipient, $subject,
            $body,
            $context,
            $unsubscribe_url,
            $unsubscribe_label,
            $supports_go_to_action
        );
        $attachments = !empty($additional['attachments']) && is_array($additional['attachments']) ? $additional['attachments'] : null;

        self::getAdapter()->send($sender, $recipient, $subject, $body, $context, $attachments, self::$on_sent);
    }

    /**
     * @var callable|null
     */
    private static $on_sent;

    /**
     * Set handler that is triggered when message is sent.
     */
    public static function onSent(callable $callback = null)
    {
        if (is_callable($callback) || $callback === null) {
            self::$on_sent = $callback;
        } else {
            throw new InvalidParamError('callback', $callback);
        }
    }

    // ---------------------------------------------------
    //  Configuration Options
    // ---------------------------------------------------

    /**
     * Return connection type.
     *
     * @return string
     */
    public static function getConnectionType()
    {
        return MAILING_ADAPTER;
    }

    private static ?array $from_email_and_name = null;

    /**
     * Return email address and name that are used to set From email parameters.
     */
    public static function getFromEmailAndName(): array
    {
        if (self::$from_email_and_name === null) {
            if (AngieApplication::isOnDemand()
                && defined('MAILING_MESSAGE_FROM_EMAIL')
                && defined('MAILING_MESSAGE_FROM_NAME')
            ) {
                self::$from_email_and_name = [
                    MAILING_MESSAGE_FROM_EMAIL,
                    MAILING_MESSAGE_FROM_NAME,
                ];
            } else {
                /** @var EmailIntegration $email_integration */
                $email_integration = Integrations::findFirstByType(EmailIntegration::class);

                self::$from_email_and_name = [
                    $email_integration->getSenderEmail(),
                    $email_integration->getSenderName(),
                ];

                if (empty(self::$from_email_and_name[1])) {
                    self::$from_email_and_name[1] = AngieApplication::getName();
                }
            }
        }

        return self::$from_email_and_name;
    }
}
