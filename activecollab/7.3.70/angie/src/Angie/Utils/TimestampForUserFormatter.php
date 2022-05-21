<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils;

use Angie\Globalization;
use DateTimeValue;
use DateValue;
use User;

class TimestampForUserFormatter
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function formatTimestamp(DateTimeValue $date_time)
    {
        $date = new DateValue($date_time->getTimestamp());

        $formatted_system_time = $date_time->formatTimeForUser($this->user, $this->getSystemGmtOffset($date));
        $formatted_user_time = $date_time->formatTimeForUser($this->user, $this->getUserGmtOffset($date));

        if ($formatted_system_time != $formatted_user_time) {
            $formatted_system_date = $date_time->formatDateForUser($this->user, $this->getSystemGmtOffset($date));
            $formatted_user_date = $date_time->formatDateForUser($this->user, $this->getUserGmtOffset($date));

            if ($formatted_system_date != $formatted_user_date) {
                return lang(
                    ':system_time (:user_time your time)',
                    [
                        'system_time' => $formatted_system_time,
                        'user_time' => "$formatted_user_date $formatted_user_time",
                    ],
                    true,
                    $this->user->getLanguage()
                );
            } else {
                return lang(
                    ':system_time (:user_time your time)',
                        [
                        'system_time' => $formatted_system_time,
                        'user_time' => $formatted_user_time,
                    ],
                    true,
                    $this->user->getLanguage()
                );
            }
        } else {
            return $formatted_system_time;
        }
    }

    private function getSystemGmtOffset(DateValue $date)
    {
        return Globalization::getGmtOffsetOnDate($date, true);
    }

    private function getUserGmtOffset(DateValue $date)
    {
        return Globalization::getUserGmtOffsetOnDate($this->user, $date);
    }
}
