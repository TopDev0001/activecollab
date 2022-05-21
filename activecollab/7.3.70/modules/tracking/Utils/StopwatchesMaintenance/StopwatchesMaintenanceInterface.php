<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\StopwatchesMaintenance;

interface StopwatchesMaintenanceInterface
{
    public function shouldRun(): bool;
    public function run(): void;
    public function getForMaintenance(): self;
    public function calculateDelayForDailyCapacity(array $stopwatch): int;
    public function calculateDelayForStopwatchMaximum(array $stopwatch): int;
}
