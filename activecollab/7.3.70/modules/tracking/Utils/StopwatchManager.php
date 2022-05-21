<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils;

use ActiveCollab\Module\Tracking\Services\StopwatchServiceInterface;
use Angie\Notifications\NotificationsInterface;
use CompositeCollection;
use ConfigOptions;
use DateTimeValue;
use DB;
use Stopwatch;
use Stopwatches;
use Task;
use User;

class StopwatchManager implements StopwatchManagerInterface
{
    protected NotificationsInterface $notifications_service;
    protected DateTimeValue $datetime_now;

    public function __construct(NotificationsInterface $notifications_service)
    {
        $this->notifications_service = $notifications_service;
        $this->datetime_now = new DateTimeValue('now');
    }

    public function setDateTimeNow(DateTimeValue $dateTime): StopwatchManagerInterface
    {
        $this->datetime_now = $dateTime;

        return $this;
    }

    public function create(array $attributes): Stopwatch
    {
        return Stopwatches::create($attributes);
    }

    public function update(Stopwatch $stopwatch, array $attributes): Stopwatch
    {
        return Stopwatches::update($stopwatch, $attributes);
    }

    public function delete(Stopwatch $stopwatch): bool
    {
        return Stopwatches::scrap($stopwatch);
    }

    public function getRunningStopwatchForUser(int $user_id): ?Stopwatch
    {
        return Stopwatches::findOne(
            [
                'conditions' => ['user_id = ? AND started_on IS NOT NULL', $user_id],
                'order' => '`id` DESC',
            ]
        );
    }

    public function findOneByUserAndId(int $user_id, int $id): ?Stopwatch
    {
        return Stopwatches::findOne(
            [
                'conditions' => ['user_id = ? AND id = ?', $user_id, $id],
            ]
        );
    }

    public function getStopwatchesCollectionForUser(User $user): CompositeCollection
    {
        return Stopwatches::prepareCollection('user_stopwatches_for_' . $user->getId(), $user);
    }

    public function getStopwatchForTypeAndUser(string $parent_type, int $parent_id, int $user_id)
    {
        return Stopwatches::findOneBy([
            'user_id' => $user_id,
            'parent_type' => $parent_type,
            'parent_id' => $parent_id,
        ]);
    }

    /**
     * Returns all stopwatches that match criteria
     * Criteria : started_on 12:00:00 - and now is 13:00:00
     * if started_on + daily_capacity < 3600 seconds.
     * @throws \DBQueryError
     * @throws \InvalidParamError
     */
    public function findStopwatchesForDailyCapacityNotification(): array
    {
        $global_daily_capacity = $this->getGlobalUserDailyCapacity();

        $query = '
SELECT s.*, u.daily_capacity FROM stopwatches s 
JOIN users u ON s.user_id = u.id 
WHERE u.is_archived = 0 
AND u.is_trashed = 0 
AND s.started_on IS NOT NULL
AND s.notification_sent_at IS NULL
';
        $should_manage = [];
        $result = DB::execute($query);
        if ($result) {
            foreach ($result as $row) {
                if ($this->isStopwatchForDailyMaintenanceJob($row['started_on'], (float) $global_daily_capacity, (float) $row['daily_capacity'])) {
                    $should_manage[] = $row;
                }
            }
        }

        return $should_manage;
    }

    public function findStopwatchesForMaximumCapacityNotification(): array
    {
        $should_manage = [];

        $result = DB::execute('
            SELECT s.* FROM stopwatches s JOIN users u ON s.user_id = u.id 
            WHERE u.is_archived = 0 
                AND u.is_trashed = 0 
                AND s.started_on IS NOT NULL
        ');

        if ($result) {
            foreach ($result as $row) {
                if ($this->isStopwatchForMaximumMaintenanceJob($row['started_on'], (int) $row['elapsed'])) {
                    $should_manage[] = $row;
                }
            }
        }

        return $should_manage;
    }

    public function isStopwatchForDailyMaintenanceJob(string $started_on, float $global_daily_capacity, ?float $daily_capacity): bool
    {
        $started_on = new DateTimeValue($started_on);
        $one_hour_before = (int) $this->datetime_now->getTimestamp() - 3600;
        $one_hour_after = (int) $this->datetime_now->getTimestamp() + 3600;

        if (!$daily_capacity) {
            $daily_capacity = $global_daily_capacity;
        }
        $daily_capacity_reach = (int) ($started_on->getTimestamp() + ($daily_capacity * 3600));
        if ($daily_capacity_reach >= $one_hour_before && $daily_capacity_reach <= $one_hour_after) {
            return true;
        }

        return false;
    }

    public function isStopwatchForMaximumMaintenanceJob(string $started_on, int $elapsed): bool
    {
        $started_on = new DateTimeValue($started_on);
        $now = $this->datetime_now->getTimestamp();
        $limit = StopwatchServiceInterface::STOPWATCH_MAXIMUM;
        $totalElapsed = (int) ($now - $started_on->getTimestamp() + $elapsed);
        if ($totalElapsed >= $limit - 3600) {
            return true;
        }

        return false;
    }

    public function sendNotificationForMaximumReached(Stopwatch $stopwatch): void
    {
        $user = $stopwatch->getUser();

        $parent = $stopwatch->getParent();
        if (!$parent) return;

        if ($stopwatch->getParentType() === Task::class) {
            /** @var Task $task */
            $task = $stopwatch->getParent();
            $description = sprintf('#%s: %s', $task->getTaskNumber(), $task->getName());
        } else {
            $description = $stopwatch->getParent()->getName();
        }

        if ($user) {
            $this->notifications_service
                ->notifyAbout('tracking/stopwatch_maximum_reached', $stopwatch)
                ->setDescription($description)
                ->setUrl($stopwatch->getViewUrl())
                ->sendToUsers([$user]);
        }
    }

    public function sendNotificationForDailyCapacity(Stopwatch $stopwatch): void
    {
        $user = $stopwatch->getUser();

        $parent = $stopwatch->getParent();
        if (!$parent) return;

        if ($user) {
            $capacity = $stopwatch->getUser()->getDailyCapacity() ?? $this->getGlobalUserDailyCapacity();
            $this->notifications_service
                ->notifyAbout('tracking/stopwatch_daily_capacity_exceed', $stopwatch)
                ->setDailyCapacity($capacity)
                ->setUrl($stopwatch->getViewUrl())
                ->sendToUsers([$user], true);
        }
    }

    public function findStopwatchesForMaximumCapacity()
    {
        $now = $this->datetime_now->format(DATETIME_MYSQL);

        $query = '
SELECT s.* 
FROM stopwatches s
JOIN users u ON s.user_id = u.id
WHERE TIMESTAMPDIFF(SECOND, s.started_on, ? ) + s.elapsed >= ? AND s.started_on IS NOT NULL
AND u.is_archived = 0
AND u.is_trashed = 0
';

        return Stopwatches::findBySQL($query, $now, StopwatchServiceInterface::STOPWATCH_MAXIMUM);
    }

    public function findStopwatchesForDailyCapacity(float $daily_capacity)
    {
        $now = $this->datetime_now->format(DATETIME_MYSQL);

        $query = '
SELECT s.*
FROM stopwatches s
JOIN users u ON s.user_id = u.id
WHERE (
    (u.daily_capacity IS NOT NULL AND TIMESTAMPDIFF(SECOND, s.started_on, ? ) >= (u.daily_capacity * 3600))
    OR (u.daily_capacity IS NULL AND TIMESTAMPDIFF(SECOND, s.started_on, ? ) >= ( ? * 3600))
)
AND s.started_on IS NOT NULL
AND u.is_archived = 0
AND u.is_trashed = 0
AND s.notification_sent_at IS NULL
';

        return Stopwatches::findBySQL($query, $now, $now, $daily_capacity);
    }

    public function getGlobalUserDailyCapacity(): float
    {
        return ConfigOptions::getValue('user_daily_capacity');
    }

    public function updateStopwatchesMaximumReach(): void
    {
        $maximum = StopwatchServiceInterface::STOPWATCH_MAXIMUM;
        DB::execute('UPDATE stopwatches SET elapsed = ? WHERE started_on IS NULL AND elapsed > ?', $maximum, $maximum);
    }
}
