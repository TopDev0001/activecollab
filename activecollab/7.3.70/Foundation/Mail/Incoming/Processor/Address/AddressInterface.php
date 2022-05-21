<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Address;

interface AddressInterface
{
    public function getNormalizedAddress(): string;
    public function getTag(): ?string;
    public function getFullAddress(): string;
}
