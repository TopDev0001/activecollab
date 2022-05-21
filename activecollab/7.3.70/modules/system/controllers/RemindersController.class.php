<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('fw_reminders', RemindersFramework::NAME);

/**
 * Application level reminders controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controller
 */
class RemindersController extends FwRemindersController
{
}
