<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet\Resolver;

use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet\MailboxesSet;
use ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet\MailboxesSetInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;

class MailboxesSetResolver implements MailboxesSetResolverInterface
{
    private OnDemandStatusInterface $on_demand_status;
    private AccountIdResolverInterface $account_id_resolver;
    private DefaultSenderResolverInterface $default_sender_resolver;

    public function __construct(
        OnDemandStatusInterface $on_demand_status,
        AccountIdResolverInterface $account_id_resolver,
        DefaultSenderResolverInterface $default_sender_resolver
    )
    {
        $this->on_demand_status = $on_demand_status;
        $this->account_id_resolver = $account_id_resolver;
        $this->default_sender_resolver = $default_sender_resolver;
    }

    public function resolveMailboxesSet(): MailboxesSetInterface
    {
        $default_sender = $this->default_sender_resolver->getDefaultSender();

        if ($this->on_demand_status->isOnDemand()) {
            $default_sender_bits = explode('@', $default_sender);

            return new MailboxesSet(
                sprintf(
                    '%s-%d@%s',
                    $default_sender_bits[0],
                    $this->account_id_resolver->getAccountId(),
                    $default_sender_bits[1]
                ),
                sprintf('notifications-%d@activecollab.com', $this->account_id_resolver->getAccountId()),
            );
        }

        return new MailboxesSet($default_sender);
    }
}
