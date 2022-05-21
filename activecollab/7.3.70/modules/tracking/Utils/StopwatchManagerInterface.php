<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils;

use CompositeCollection;
use DateTimeValue;
use Stopwatch;
use User;

interface StopwatchManagerInterface
{
    public function setDateTimeNow(DateTimeValue $dateTimeValue): StopwatchManagerInterface;

    public function create(array $attributes): Stopwatch;

    public function update(Stopwatch $stopwatch, array $attributes): Stopwatch;

    public function getRunningStopwatchForUser(int $user_id): ?Stopwatch;

    public function findOneByUserAndId(int $user_id, int $id): ?Stopwatch;

    public function getStopwatchesCollectionForUser(User $user): CompositeCollection;

    public function delete(Stopwatch $stopwatch): bool;

    public function getStopwatchForTypeAndUser(string $parent_type, int $parent_id, int $user_id);

    public function findStopwatchesForDailyCapacityNotification(): array;

    public function findStopwatchesForMaximumCapacityNotification(): array;

    public function findStopwatchesForDailyCapacity(float $daily_capacity);

    public function findStopwatchesForMaximumCapacity();

    public function getGlobalUserDailyCapacity(): float;

    public function sendNotificationForMaximumReached(Stopwatch $stopwatch): void;

    public function sendNotificationForDailyCapacity(Stopwatch $stopwatch): void;

    public function updateStopwatchesMaximumReach(): void;

    public function isStopwatchForDailyMaintenanceJob(string $started_on, float $global_daily_capacity, ?float $daily_capacity): bool;

    public function isStopwatchForMaximumMaintenanceJob(string $started_on, int $elapsed): bool;
}
