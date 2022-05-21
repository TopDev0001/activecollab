<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface AvailabilityTypeInterface
{
    const LEVEL_AVAILABLE = 'available';
    const LEVEL_NOT_AVAILABLE = 'not_available';

    const LEVELS = [
        self::LEVEL_AVAILABLE,
        self::LEVEL_NOT_AVAILABLE,
    ];

    public function isAvailable(): bool;
}
