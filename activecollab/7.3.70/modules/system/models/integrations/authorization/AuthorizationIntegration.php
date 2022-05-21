<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Authentication\Adapter\BrowserSessionAdapter;
use ActiveCollab\Authentication\Adapter\TokenBearerAdapter;
use ActiveCollab\Cookies\CookiesInterface;
use Angie\Authentication\Adapter\AngieTokenHeader;
use Angie\Authentication\Repositories\SessionsRepository;
use Angie\Authentication\Repositories\TokensRepository;
use Angie\Authentication\Repositories\UsersRepository;

abstract class AuthorizationIntegration extends Integration implements AuthorizationIntegrationInterface
{
    private array $adapters = [];

    public function getAdapters()
    {
        if (empty($this->adapters)) {
            $users_repository = new UsersRepository();
            $sessions_repository = new SessionsRepository();
            $tokens_repository = new TokensRepository();

            $this->adapters = [
                new TokenBearerAdapter($users_repository, $tokens_repository),
                new AngieTokenHeader($users_repository, $tokens_repository),
                new BrowserSessionAdapter(
                    $users_repository,
                    $sessions_repository,
                    AngieApplication::getContainer()->get(CookiesInterface::class),
                    AngieApplication::getSessionIdCookieName()
                ),
            ];
        }

        return $this->adapters;
    }

    public function canInviteUsers()
    {
        return $this->canInviteOwners() || $this->canInviteMembers() || $this->canInviteClients();
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'can_invite_owners' => $this->canInviteOwners(),
                'can_invite_members' => $this->canInviteMembers(),
                'can_invite_clients' => $this->canInviteClients(),
                'can_invite_users' => $this->canInviteUsers(),
            ]
        );
    }
}
