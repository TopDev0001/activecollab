<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ValueContainer;

interface ValueContainerInterface
{
    public function hasValue(): bool;
    public function getValue();
}
