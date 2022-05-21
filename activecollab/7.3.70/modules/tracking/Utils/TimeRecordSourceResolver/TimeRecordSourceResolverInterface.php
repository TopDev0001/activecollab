<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\TimeRecordSourceResolver;

use ActiveCollab\Authentication\AuthenticationResult\AuthenticationResultInterface;

interface TimeRecordSourceResolverInterface
{
    const TIMER_APP = 'timer_app';
    const BUILT_IN_TIMER = 'built_in_timer';
    const MY_TIME = 'my_time';
    const MY_TIMESHEET = 'my_timesheet';
    const TASK_SIDEBAR = 'task_sidebar';
    const PROJECT_TIME = 'project_time';
    const PROJECT_TIMESHEET = 'project_timesheet';
    const API_CONSUMER = 'api_consumer';
    const UNKNOWN = 'unknown';

    const TIME_RECORDS_VALUES = [
        self::TIMER_APP,
        self::BUILT_IN_TIMER,
        self::MY_TIME,
        self::MY_TIMESHEET,
        self::TASK_SIDEBAR,
        self::PROJECT_TIME,
        self::PROJECT_TIMESHEET,
        self::API_CONSUMER,
    ];

    public function getSource(
        AuthenticationResultInterface $authentication_result,
        ?string $source = null
    ): string;
}
