<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use ConfigOptions;
use DateTime;
use DateTimeValue;
use DateTimeZone;
use DateValue;
use DB;
use User;

class PushNotificationScheduleMatcher implements PushNotificationScheduleMatcherInterface
{
    private $default_config_options;
    private $personalised_config_options;
    private array $unavailable_users_ids;
    private DateTimeValue $now;
    private DateValue $today;
    private PushScheduleDaysOffResolverInterface $day_off_resolver;
    private bool $initialized = false;

    public function __construct(PushScheduleDaysOffResolverInterface $day_off_resolver) {
        $this->day_off_resolver = $day_off_resolver;
        $this->today = new DateValue();
        $this->now = DateTimeValue::now();
    }

    public function match(array $user_ids): array
    {
        if (!$this->initialized) {
            $this->initialize($user_ids);
            $this->initialized = true;
        }

        $resolved = [];

        foreach ($user_ids as $user_id){
            $schedule = new PushSchedule(
                $this->day_off_resolver,
                DateTime::createFromFormat(
                    DATETIME_MYSQL,
                    $this->now->toMySQL(),
                    new DateTimeZone('UTC')
                )
            );

            $schedule->setUserId($user_id)
                ->setAbsent(in_array($user_id, $this->unavailable_users_ids))
                ->setDefaultSettings($this->default_config_options ? $this->default_config_options->toArray() : [])
                ->setPersonalSettings($this->personalised_config_options ? $this->personalised_config_options->toArray() : [])
                ->resolveSettings();

            if ($schedule->shouldReceivePush()) {
                $resolved[] = $user_id;
            }
        }

        return $resolved;
    }

    public function matchForUser(User $user): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $schedule = new PushSchedule(
            $this->day_off_resolver,
            DateTime::createFromFormat(
                DATETIME_MYSQL,
                $this->now->toMySQL(),
                new DateTimeZone('UTC')
            )
        );

        $schedule->setUserId($user->getId())
            ->setAbsent(in_array($user->getId(), $this->unavailable_users_ids))
            ->setSettings(ConfigOptions::getValuesFor([
                'time_timezone',
                'time_workdays',
                'push_notification_schedule',
                'push_notification_schedule_settings',
            ], $user));

        return $schedule->shouldReceivePush();
    }

    private function initialize($user_ids = null)
    {
        if ($user_ids) {
            $this->default_config_options = DB::execute('SELECT name, value FROM config_options WHERE name IN(?)', [
                'time_timezone',
                'time_workdays',
                'push_notification_schedule',
                'push_notification_schedule_settings',
            ]);

            $this->personalised_config_options = DB::execute(
                'SELECT name, value, parent_id FROM config_option_values WHERE parent_type = ? AND parent_id IN (?) AND name IN (?)',
                'User',
                $user_ids,
                [
                    'time_timezone',
                    'push_notification_schedule',
                    'push_notification_schedule_settings',
                ]
            );
        }
        $this->unavailable_users_ids = DB::executeFirstColumn('
SELECT user_id 
FROM availability_records 
WHERE (start_date BETWEEN ? AND ? OR end_date BETWEEN ? AND ? OR (start_date <= ? AND end_date >= ?))
', $this->today, $this->today, $this->today, $this->today, $this->today, $this->today) ?? [];
        $this->initialized = true;
    }
}
