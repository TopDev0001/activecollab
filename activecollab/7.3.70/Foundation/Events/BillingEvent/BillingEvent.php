<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Events\BillingEvent;

use JsonSerializable;

abstract class BillingEvent implements BillingEventInterface, JsonSerializable
{
    public function getPayloadVersion(): string
    {
        return '1.0';
    }

    public function jsonSerialize(): array
    {
        return [
            'version' => $this->getPayloadVersion(),
        ];
    }
}
