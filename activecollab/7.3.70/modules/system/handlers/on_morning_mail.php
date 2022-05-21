<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_morning_mail event handler.
 *
 * @package ActiveCollab.modules.system
 * @subpackage handlers
 */

/**
 * Send out morning paper.
 */
function system_handle_on_morning_mail()
{
    AngieApplication::morningMailResolver()->getMorningMailManager()->send(DateTimeValue::now()->getSystemDate());
}
