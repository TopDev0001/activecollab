<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\Mode;

interface ApplicationModeInterface
{
    const IN_DEVELOPMENT = 'development';
    const IN_DEBUG_MODE = 'debug';
    const IN_PRODUCTION = 'production';

    const MODES = [
        self::IN_DEVELOPMENT,
        self::IN_DEBUG_MODE,
        self::IN_PRODUCTION,
    ];

    public function isInDevelopment(): bool;
    public function isInDebugMode(): bool;
    public function isInProduction(): bool;
    public function isInTestMode(): bool;
}
