<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Events\BillingEvent;

use ActiveCollab\Foundation\Events\EventInterface;

interface BillingEventInterface extends EventInterface
{
    const WEBHOOK_CONTEXT_BILLING = 'Billing';

    public function getPayloadVersion(): string;
}
