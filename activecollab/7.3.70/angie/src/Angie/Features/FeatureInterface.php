<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Features;

interface FeatureInterface
{
    public function getName(): string;
    public function getVerbose(): string;
    public function getAddOnsAvailableOn(): array;
    public function activate(): bool;
    public function deactivate(): bool;
    public function enable(): bool;
    public function disable(): bool;
    public function isEnabled(): bool;
    public function isLocked(): bool;
}
