<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

abstract class FwReminder extends BaseReminder implements RoutingContextInterface
{
    public function isParentOptional(): bool
    {
        return false;
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'comment' => $this->getComment(),
                'send_on' => $this->getSendOn(),
                'subscribers' => $this->getSubscribersAsArray(), // This is recipients list, and it needs to be included in general reminder JSON
            ]
        );
    }

    /**
     * Send a reminder.
     */
    abstract public function send();

    public function canView(User $user): bool
    {
        return $this->isCreatedBy($user);
    }

    public function canEdit(User $user): bool
    {
        return $this->isCreatedBy($user);
    }

    public function canDelete(User $user): bool
    {
        return $this->isCreatedBy($user);
    }

    public function getRoutingContext(): string
    {
        return 'reminder';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'reminder_id' => $this->getId(),
        ];
    }

    public function touchParentOnPropertyChange(): ?array
    {
        return [
            'parent_type',
            'parent_id',
        ];
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->validatePresenceOf('send_on') or $errors->addError('Reminder time is required', 'send_on');

        parent::validate($errors);
    }

    public function whoCanSeeThis(): array
    {
        return [$this->getCreatedById()];
    }

    public function canUserSeeThis(User $user): bool
    {
        return $user->getId() === $this->getCreatedById();
    }
}
