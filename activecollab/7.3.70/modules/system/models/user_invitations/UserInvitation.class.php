<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\RouterInterface;

/**
 * User invitation class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
final class UserInvitation extends BaseUserInvitation
{
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'code' => $this->getCode(),
        ]);
    }

    public function describeSingleForFeather(array & $result)
    {
        parent::describeSingleForFeather($result);

        if ($invited_to = $this->getInvitedTo()) {
            $result['invited_to'] = ['id' => $invited_to->getId(), 'class' => get_class($invited_to), 'name' => $invited_to->getName()];
        } else {
            $result['invited_to'] = null;
        }
    }

    /**
     * Return invited user instance.
     *
     * @return User|DataObject
     */
    public function getUser()
    {
        return DataObjectPool::get(User::class, $this->getUserId());
    }

    /**
     * @return DataObject|null
     */
    public function getInvitedTo()
    {
        return $this->getInvitedToType() && $this->getInvitedToId() ?
            DataObjectPool::get($this->getInvitedToType(), $this->getInvitedToId()) :
            null;
    }

    /**
     * @param  DataObject|null      $value
     * @throws InvalidInstanceError
     */
    public function setInvitedTo($value)
    {
        if ($value instanceof DataObject) {
            $this->setInvitedToType(get_class($value));
            $this->setInvitedToId($value->getId());
        } else {
            if ($value === null) {
                $this->setInvitedToType(null);
                $this->setInvitedToId(0);
            } else {
                throw new InvalidInstanceError('value', $value, DataObject::class);
            }
        }
    }

    const USER_NOT_FOUND = 'user_not_found';
    const USER_INACTIVE = 'user_inactive';
    const ALREADY_ACCEPTED = 'already_accepted';
    const ACCEPTABLE = 'acceptable';

    /**
     * Return invitation status.
     */
    public function getStatus()
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            if ($user->getIsArchived() || $user->getIsTrashed()) {
                return self::USER_INACTIVE;
            }

            return $user->getLastLoginOn() ? self::ALREADY_ACCEPTED : self::ACCEPTABLE;
        } else {
            return self::USER_NOT_FOUND;
        }
    }

    /**
     * @return string
     */
    public function getAcceptUrl()
    {
        return AngieApplication::getContainer()
            ->get(RouterInterface::class)
                ->assemble(
                    'accept_invitation',
                    [
                        'user_id' => $this->getUserId(),
                        'code' => $this->getCode(),
                    ]
                );
    }

    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('user_id')) {
            $this->validateUniquenessOf('user_id') or $errors->fieldValueNeedsToBeUnique('user_id');
        } else {
            $errors->fieldValueIsRequired('user_id');
        }
        $this->validatePresenceOf('code') or $errors->fieldValueIsRequired('code');

        parent::validate($errors);
    }
}
