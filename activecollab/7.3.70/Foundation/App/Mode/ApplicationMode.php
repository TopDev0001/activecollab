<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\App\Mode;

class ApplicationMode implements ApplicationModeInterface
{
    private string $application_mode;
    private bool $is_in_test;

    public function __construct(string $application_mode, bool $is_in_test)
    {
        $this->application_mode = $application_mode;
        $this->is_in_test = $is_in_test;
    }

    public function isInDevelopment(): bool
    {
        return $this->application_mode === self::IN_DEVELOPMENT;
    }

    public function isInDebugMode(): bool
    {
        return $this->application_mode === self::IN_DEBUG_MODE;
    }

    public function isInProduction(): bool
    {
        return $this->application_mode === self::IN_PRODUCTION;
    }

    public function isInTestMode(): bool
    {
        return $this->is_in_test;
    }
}
