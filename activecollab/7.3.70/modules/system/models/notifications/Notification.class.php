<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Foundation\Notifications\NotificationInterface;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\NotificationRecipientEvents\NotificationRecipientCreatedEvent;
use Angie\Mailer;
use Angie\Mailer\Decorator\Decorator;
use Angie\Notifications\PushNotificationInterface;

abstract class Notification extends BaseNotification implements RoutingContextInterface
{
    /**
     * Cached short name.
     *
     * @var string
     */
    private $short_name = false;

    /**
     * Serialize to JSON.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['sender_id'] = $this->getSenderId();

        if (empty($result['sender_id'])) {
            $sender = $this->getSender();

            if ($sender instanceof AnonymousUser) {
                $result['sender_name'] = $sender->getName();
                $result['sender_email'] = $sender->getEmail();
            } else {
                $result['sender_name'] = lang('Unknown');
                $result['sender_email'] = 'noreply@activecollab.com';
            }
        }

        return $result;
    }

    public function getShortName(): string
    {
        if ($this->short_name === false) {
            $class_name = get_class($this);

            $this->short_name = Angie\Inflector::underscore(substr($class_name, 0, strlen($class_name) - 12));
        }

        return $this->short_name;
    }

    public function getTemplatePath(NotificationChannel $channel): string
    {
        return AngieApplication::notifications()->getNotificationTemplatePath($this, $channel);
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [];
    }

    /**
     * Return notification sender.
     *
     * @return IUser|null
     */
    public function getSender()
    {
        return $this->getUserFromFieldSet('sender');
    }

    /**
     * Set notification sender.
     *
     * @param  IUser|null $user
     * @return IUser|null
     */
    public function setSender($user)
    {
        return $this->setUserFromFieldSet($user, 'sender');
    }

    /**
     * Custom notification decorator.
     *
     * @var Decorator
     */
    private $decorator = false;

    /**
     * @return $this
     */
    public function &setDecorator(Decorator $decorator)
    {
        $this->decorator = $decorator;

        return $this;
    }

    /**
     * @return Decorator
     */
    public function getDecorator()
    {
        return $this->decorator instanceof Decorator ? $this->decorator : Mailer::getDecorator();
    }

    public function isSender(IUser $user): bool
    {
        if ($user instanceof User) {
            return $this->getSenderId() == $user->getId();
        }

        return strcasecmp((string) $this->getSenderEmail(), $user->getEmail()) == 0;
    }

    protected function getMentionsFromParent(): bool
    {
        return true;
    }

    /**
     * Set notification parent instance.
     *
     * @param  ApplicationObject $parent
     * @param  bool              $save
     * @return ApplicationObject
     */
    public function setParent($parent, $save = false)
    {
        if ($parent instanceof IBody && $this->getMentionsFromParent() && is_foreachable($parent->getNewMentions())) {
            $this->setMentionedUsers($parent->getNewMentions());
        }

        return parent::setParent($parent, $save);
    }

    /**
     * Return files attached to this notification, if any.
     *
     * @return array
     */
    public function getAttachments(NotificationChannel $channel)
    {
        return [];
    }

    /**
     * Return visit URL.
     *
     * @return string
     */
    public function getVisitUrl(IUser $user)
    {
        return $this->getParent() instanceof RoutingContextInterface ? $this->getParent()->getViewUrl() : '#';
    }

    public function getUnsubscribeUrl(IUser $user): string
    {
        $parent = $this->getParent();

        if ($parent instanceof ISubscriptions) {
            return AngieApplication::getContainer()
                ->get(RouterInterface::class)
                    ->assemble(
                        'public_notifications_unsubscribe',
                        [
                            'code' => $parent->getSubscriptionCodeFor($user),
                        ],
                    );
        }

        return '';
    }

    public function getUnsubscribeLabel(IUser $user): string
    {
        $context = $this->getParent();

        if ($context instanceof ISubscriptions) {
            return lang(
                'Unsubscribe from this :type',
                [
                    'type' => $context->getVerboseType(true, $user->getLanguage()),
                ],
                true,
                $user->getLanguage(),
            );
        }

        return '';
    }

    public function getRoutingContext(): string
    {
        return 'notification';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'notification_id' => $this->getId(),
        ];
    }

    // ---------------------------------------------------
    //  Repipients
    // ---------------------------------------------------

    /**
     * Return true if $user is recipient of this notification.
     *
     * @TODO Add caching support?
     *
     * @return bool
     */
    public function isRecipient(IUser $user)
    {
        if ($user instanceof User) {
            return (bool) DB::executeFirstCell(
                'SELECT COUNT(id) AS "row_count" FROM notification_recipients WHERE notification_id = ? AND recipient_id = ?',
                $this->getId(),
                $user->getId(),
            );
        }

        return (bool) DB::executeFirstCell(
            'SELECT COUNT(id) AS "row_count" FROM notification_recipients WHERE notification_id = ? AND recipient_email = ?',
            $this->getId(),
            $user->getEmail(),
        );
    }

    /**
     * Cached array of recipients.
     *
     * @var array
     */
    private $recipients = false;

    /**
     * Return array of notification recipients.
     *
     * @param  bool  $use_cache
     * @return array
     */
    public function getRecipients($use_cache = true)
    {
        if (empty($use_cache) || $this->recipients === false) {
            $this->recipients = Users::findOnlyUsersFromUserListingTable(
                'notification_recipients',
                'recipient',
                DB::prepare('notification_recipients.notification_id = ?', $this->getId()),
            );
        }

        return $this->recipients;
    }

    /**
     * Add recipients to this notification.
     *
     * @param User|User[] $r
     */
    public function addRecipient($r)
    {
        if ($r instanceof User) {
            $r = [$r];
        }

        if (!is_foreachable($r)) {
            throw new InvalidParamError('r', $r, '$r is expected to be one or more IUser instances');
        }

        $notification_id = $this->getId();

        foreach ($r as $recipient) {
            $notification_recipient = NotificationRecipients::create(
                [
                    'notification_id' => $notification_id,
                    'recipient_id' => $recipient->getId(),
                    'recipient_name' => $recipient->getName(),
                    'recipient_email' => $recipient->getEmail(),
                    'is_mentioned' => $this->isUserMentioned($recipient),
                ],
            );

            if ($notification_recipient instanceof NotificationRecipient && $this->getParentType() && $this->getParentId()) {
                DataObjectPool::announce(new NotificationRecipientCreatedEvent($notification_recipient));
            }
        }
    }

    /**
     * Remove one or more recipients from this notification.
     *
     * @param User|User[] $r
     */
    public function removeRecipient($r)
    {
        if ($r instanceof User) {
            $r = [$r];
        }

        if (is_foreachable($r)) {
            $recipient_ids = [];

            foreach ($r as $recipient) {
                $recipient_ids[] = $recipient->getId();
            }

            NotificationRecipients::deleteBy([$this->getId()], $recipient_ids);
        }
    }

    /**
     * Remove all recipients from this notification.
     */
    public function clearRecipients()
    {
        NotificationRecipients::deleteBy([$this->getId()]);
        $this->recipients = false;
    }

    /**
     * Return true if $user read this notification.
     *
     * @param  bool $use_cache
     * @return bool
     */
    public function isRead(User $user, $use_cache = true)
    {
        return Notifications::isRead($this, $user, $use_cache);
    }

    public function sendToAdministrators(bool $skip_sending_queue = false): NotificationInterface
    {
        return $this->sendToUsers(Users::findOwners(), $skip_sending_queue);
    }

    public function sendToFinancialManagers(
        bool $skip_sending_queue = false,
        array $exclude_user = null
    ): NotificationInterface
    {
        $notify_people = null;

        if ($this instanceof InvoicePaidNotification) {
            $notify_managers = ConfigOptions::getValue('invoice_notify_financial_managers'); //only for InvoicePaidNotification

            if ($notify_managers == Invoice::INVOICE_NOTIFY_FINANCIAL_MANAGERS_ALL) {
                $notify_people = Invoices::findFinancialManagers($exclude_user);
            } elseif ($notify_managers == Invoice::INVOICE_NOTIFY_FINANCIAL_MANAGERS_SELECTED) {
                $notify_manager_ids = ConfigOptions::getValue('invoice_notify_financial_manager_ids');
                if (is_foreachable($notify_manager_ids)) {
                    $notify_people = []; //check is user still financial manager

                    foreach ($notify_manager_ids as $user_id) {
                        $user = DataObjectPool::get('User', $user_id);
                        if ($user instanceof User && $user->isFinancialManager()) {
                            if ($exclude_user instanceof User && $exclude_user->getId() == $user->getId()) {
                                continue; // skip if user is exclude user
                            }

                            $notify_people[] = $user;
                        }
                    }
                }
            }
        } else {
            $notify_people = Invoices::findFinancialManagers();
        }

        if ($notify_people) {
            $this->sendToUsers($notify_people, $skip_sending_queue);
        }

        return $this;
    }

    public function sendToSubscribers(bool $skip_sending_queue = false): NotificationInterface
    {
        $parent = $this->getParent();

        if (!$parent instanceof ISubscriptions) {
            throw new InvalidInstanceError('parent', $parent, ISubscriptions::class);
        }

        if ($parent->hasSubscribers()) {
            return $this->sendToUsers($parent->getSubscribers(), $skip_sending_queue);
        }

        return $this;
    }

    /**
     * Send to provided group of users.
     *
     * @param  IUser|IUser[] $users
     * @return Notification
     */
    public function sendToUsers(
        $users,
        bool $skip_sending_queue = false
    ): NotificationInterface
    {
        AngieApplication::notifications()
            ->sendNotificationToRecipients($this, $users, $skip_sending_queue);

        return $this;
    }

    public function isUserMentioned(IUser $user): bool
    {
        if ($user instanceof User) {
            $mentioned_users = $this->getMentionedUsers();

            return is_array($mentioned_users) && in_array($user->getId(), $mentioned_users);
        }

        return false;
    }

    /**
     * Return array of mentioned users, if any.
     *
     * @return array|null
     */
    public function getMentionedUsers()
    {
        return $this->getAdditionalProperty('mentioned_users');
    }

    /**
     * Set array of mentioned users.
     *
     * @param  array $value
     * @return array
     */
    protected function setMentionedUsers($value)
    {
        return $this->setAdditionalProperty('mentioned_users', $value);
    }

    public function isThisNotificationVisibleToUser(IUser $user): bool
    {
        if ($this->ignoreSender() && $this->isSender($user)) {
            return false; // Ignore sender by default
        }

        return true;
    }

    public function ignoreSender(): bool
    {
        return true;
    }

    public function isUserBlockingThisNotification(IUser $user, NotificationChannel $channel = null): bool
    {
        if ($user instanceof User) {
            // @ mention override for email channel when notifications_user_send_email_mentions is set to TRUE
            if ($channel instanceof EmailNotificationChannel && $this->isUserMentioned($user) && ConfigOptions::getValueFor('notifications_user_send_email_mentions', $user)) {
                return false;
            }

            foreach ($this->optOutConfigurationOptions($channel) as $option) {
                if (!ConfigOptions::getValueFor($option, $user)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function optOutConfigurationOptions(NotificationChannel $channel = null): array
    {
        return [];
    }

    /**
     * @see https://developers.google.com/gmail/markup/reference/go-to-action
     */
    public function supportsGoToAction(IUser $recipient): bool
    {
        return false;
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof PushNotificationChannel && !$this instanceof PushNotificationInterface) {
            return false; //skip send to push channel
        }

        if ($recipient instanceof User) {
            if ($channel->isEnabledFor($recipient)) {
                // @ mention override opt out options when notifications_user_send_email_mentions is set to TRUE
                if ($channel instanceof EmailNotificationChannel
                    && $this->isUserMentioned($recipient)
                    && ConfigOptions::getValueFor('notifications_user_send_email_mentions', $recipient)
                ) {
                    return true;
                }

                foreach ($this->optOutConfigurationOptions($channel) as $option) {
                    if (!ConfigOptions::getValueFor($option, $recipient)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Set update flags for combined object updates collection.
     */
    public function onObjectUpdateFlags(array &$updates)
    {
    }

    /**
     * This method is called when we need to load related notification objects for API response.
     */
    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
    }

    /**
     * Set reaction flags for combined object updates collection.
     */
    public function onObjectReactionFlags(array &$reactions)
    {
    }

    // ---------------------------------------------------
    //  Template variables
    // ---------------------------------------------------

    /**
     * Array of additional template vars, indexed by variable name.
     */
    private array $additional_template_vars = [];

    /**
     * Set additional template variables.
     *
     * @param  mixed        $p1
     * @param  mixed        $p2
     * @return Notification
     */
    public function &setAdditionalTemplateVars($p1, $p2 = null)
    {
        if (is_array($p1)) {
            $this->additional_template_vars = array_merge($this->additional_template_vars, $p1);
        } else {
            $this->additional_template_vars[$p1] = $p2;
        }

        return $this;
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Delete notification from the database.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting notification @ ' . __CLASS__);

            NotificationRecipients::deleteBy([$this->getId()]);
            parent::delete($bulk);

            DB::commit('Notification deleted @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete notification @ ' . __CLASS__);

            throw $e;
        }
    }

    protected function getEmailSettingsUrl(): string
    {
        return ROOT_URL . '/settings';
    }
}
