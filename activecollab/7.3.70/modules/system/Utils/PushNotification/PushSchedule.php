<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

class PushSchedule
{
    private int $user_id;

    private array $default_settings;

    private array $personal_settings;

    private bool $absent = false;

    private DateTime $date_time;

    private array $settings;

    private PushScheduleDaysOffResolverInterface $day_off_resolver;

    public function __construct(
        PushScheduleDaysOffResolverInterface $day_off_resolver,
        DateTime $date_time
    ) {
        $this->day_off_resolver = $day_off_resolver;
        $this->date_time = $date_time;
    }

    public function getDefaultSettings(): array
    {
        return $this->default_settings;
    }

    public function setDefaultSettings(array $default_settings): self
    {
        $this->default_settings = $default_settings;

        return $this;
    }

    public function getPersonalSettings(): array
    {
        return $this->personal_settings;
    }

    public function setPersonalSettings(array $personal_settings): PushSchedule
    {
        $this->personal_settings = $personal_settings;

        return $this;
    }

    public function resolveSettings(): void
    {
        $settings = array_reduce($this->default_settings, function ($carry, $item) {
            $carry[$item['name']] = unserialize($item['value']);

            return $carry;
        }, []);

        $personal = array_reduce($this->personal_settings, function ($carry, $item) {
            if ($item['parent_id'] === $this->user_id){
                $carry[$item['name']] = unserialize($item['value']);
            }

            return $carry;
        }, []);
        $result = array_replace($settings, $personal);
        $this->settings = $result;
    }

    public function isAbsent(): bool
    {
        return $this->absent;
    }

    public function setAbsent(bool $absent): self
    {
        $this->absent = $absent;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setDateTime(DateTime $date_time): self
    {
        $this->date_time = $date_time;

        return $this;
    }

    public function setSettings(array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    public function shouldReceivePush(): bool
    {
        if ($this->settings['push_notification_schedule'] === 'always') {
            return true;
        }
        if ($this->settings['push_notification_schedule'] === 'workdays') {
            if ($this->isAbsent()){
                return false;
            }
            $user_time_now = clone $this->date_time;
            $user_time_now->setTimezone(new DateTimeZone($this->settings['time_timezone']));
            [$user_available_from, $user_available_to] = $this->parseTimes(
                $this->settings['push_notification_schedule_settings'][0],
                $this->settings['push_notification_schedule_settings'][1],
                $user_time_now
            );

            if ($this->isBetween($user_available_to, $user_available_from, $this->date_time)) {
                if ($this->day_off_resolver->isDayOff($user_time_now)){
                    return false;
                }
                if ($this->isWorkday($user_available_from) && $this->isWorkday($user_available_to)){
                    return true;
                }
            }

            return false;
        }

        if ($this->settings['push_notification_schedule'] === 'everyday') {
            $user_time_now = clone $this->date_time;
            $user_time_now->setTimezone(new DateTimeZone($this->settings['time_timezone']));
            [$user_available_from, $user_available_to] = $this->parseTimes(
                $this->settings['push_notification_schedule_settings'][0],
                $this->settings['push_notification_schedule_settings'][1],
                $user_time_now,
            );

            if ($this->isBetween($user_available_to, $user_available_from, $this->date_time)) {
                return true;
            }
        }

        if ($this->settings['push_notification_schedule'] === 'custom') {
            $user_time_now = clone $this->date_time;
            $user_time_now->setTimezone(new DateTimeZone($this->settings['time_timezone']));
            $current_day = $user_time_now->format('D');

            if (
                isset($this->settings['push_notification_schedule_settings'][$current_day]) &&
                !is_null($this->settings['push_notification_schedule_settings'][$current_day])
            ) {
                [$user_available_from, $user_available_to] = $this->parseTimes(
                    $this->settings['push_notification_schedule_settings'][$current_day][0],
                    $this->settings['push_notification_schedule_settings'][$current_day][1],
                    $user_time_now,
                );
                if ($this->isBetween($user_available_to, $user_available_from, $this->date_time)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isWorkday(DateTimeInterface $date_time): bool
    {
        $current = (int) $date_time->format('N');
        $js_current = $current === 7 ? 0 : $current; //we store time_workdays as [0 - 6] where 0 is Sun

        return in_array($js_current, $this->settings['time_workdays']);
    }

    public function isBetween(DateTime $upper_bound, DateTime $lower_bound, DateTime $check_date): bool
    {
        return $lower_bound <= $check_date && $check_date <= $upper_bound;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    private function parseTimes(string $from, string $to, DateTime $user_time_now): array
    {
        $parsed_from = date_parse($from);
        $parsed_to = date_parse($to);
        $user_available_from = (clone $user_time_now)->setTime($parsed_from['hour'], $parsed_from['minute']);
        $user_available_to = (clone $user_time_now)->setTime($parsed_to['hour'], $parsed_to['minute']);

        return [$user_available_from, $user_available_to];
    }
}
