<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

trait IResetInitialSettingsTimestamp
{
    public function registerIResetInitialSettingsTimestamp(): void
    {
        $this->registerEventHandler(
            'on_after_save',
            function ($is_new, $modifications) {
                if ($is_new || !empty($modifications)) {
                    AngieApplication::invalidateInitialSettingsCache();
                }
            }
        );
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;
}
