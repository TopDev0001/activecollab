<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents\TeamDeletedEvent;
use ActiveCollab\Module\System\Wires\TeamAvatarProxy;

class Team extends BaseTeam
{
    public function getRoutingContext(): string
    {
        return 'team';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'team_id' => $this->getId(),
        ];
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function canDelete(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function canEdit(User $user): bool
    {
        return $user->isPowerUser() || $this->isCreatedBy($user);
    }

    /**
     * Return team avatar URL.
     *
     * @param string|int $size
     */
    public function getAvatarUrl($size = '--SIZE--'): string
    {
        return AngieApplication::getProxyUrl(
            TeamAvatarProxy::class,
            EnvironmentFramework::INJECT_INTO,
            [
                'team_id' => $this->getId(),
                'team_name' => $this->getName(),
                'size' => $size,
                'timestamp' => $this->getUpdatedOn()->getTimestamp(),
            ]
        );
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'avatar_url' => $this->getAvatarUrl(),
            ]
        );
    }

    public function validate(ValidationErrors & $errors)
    {
        if ($this->validatePresenceOf('name')) {
            $this->validateUniquenessOf('name') or $errors->addError('Team name needs to be unique', 'name');
        } else {
            $errors->fieldValueIsRequired('name');
        }
    }

    public function delete($bulk = false): void
    {
        DataObjectPool::announce(new TeamDeletedEvent($this));

        parent::delete($bulk);
    }
}
