<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Services;

use ActiveCollab\User\UserInterface;
use Project;
use Stopwatch;
use Task;

interface StopwatchServiceInterface
{
    const ALLOWED_TYPES = [
        Task::class,
        Project::class,
    ];

    const STOPWATCH_MAXIMUM = 359999;

    public function start(UserInterface $user, string $parent_type, int $parent_id, int $elapsed): Stopwatch;

    public function resume(Stopwatch $stopwatch): Stopwatch;

    public function pause(Stopwatch $stopwatch): Stopwatch;

    public function edit(Stopwatch $stopwatch, array $attributes): Stopwatch;

    public function delete(Stopwatch $stopwatch): bool;
}
