<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

/**
 * Application level invitation notification.
 *
 * @package activeCollab.modules.system
 * @subpackage notifications
 */
class InvitationNotification extends Notification
{
    /**
     * Return user invitation instance.
     *
     * @return UserInvitation
     */
    public function getInvitation()
    {
        return DataObjectPool::get('UserInvitation', $this->getAdditionalProperty('invitation_id'));
    }

    /**
     * Set user invitation.
     *
     * @return $this
     */
    public function &setInvitation(UserInvitation $invitation)
    {
        $this->setAdditionalProperty('invitation_id', $invitation->getId());

        return $this;
    }

    /**
     * @return DataObject|null
     */
    public function getInvitedTo()
    {
        return $this->getAdditionalProperty('invited_to_type') && $this->getAdditionalProperty('invited_to_id') ? DataObjectPool::get($this->getAdditionalProperty('invited_to_type'), $this->getAdditionalProperty('invited_to_id')) : null;
    }

    /**
     * @param  DataObject|null      $value
     * @return $this
     * @throws InvalidInstanceError
     */
    public function &setInvitedTo($value)
    {
        if ($value instanceof DataObject) {
            $this->setAdditionalProperty('invited_to_type', get_class($value));
            $this->setAdditionalProperty('invited_to_id', $value->getId());
        } else {
            if ($value === null) {
                $this->setAdditionalProperty('invited_to_type', '');
                $this->setAdditionalProperty('invited_to_id', 0);
            } else {
                throw new InvalidInstanceError('value', $value, 'DataObject');
            }
        }

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        $invitation = $this->getInvitation();
        $invited_by = $invitation ? $invitation->getCreatedBy() : null;

        if (empty($invited_by)) {
            $invited_by = $this->getSender();
        }

        return [
            'invitation' => $invitation,
            'invited_by' => $invited_by,
            'invited_to' => $this->getInvitedTo(),
            'owner_company' => AngieApplication::getContainer()
                ->get(OwnerCompanyResolverInterface::class)
                    ->getCompany(),
        ];
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true; // Always deliver this notification via email
        } elseif ($channel instanceof WebInterfaceNotificationChannel) {
            return false; // Never deliver this notification to web interface
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
