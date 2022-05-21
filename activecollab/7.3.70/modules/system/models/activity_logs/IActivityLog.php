<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface IActivityLog
{
    public function gag(): void;
    public function ungag(): void;
    public function isGagged(): bool;
    public function clearActivityLogs(): void;
}
