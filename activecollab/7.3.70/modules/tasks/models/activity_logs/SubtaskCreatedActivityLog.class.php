<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Subtask created activity log.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage models.activity_logs
 */
class SubtaskCreatedActivityLog extends InstanceCreatedActivityLog
{
    use SubtaskActivityLog;
}
