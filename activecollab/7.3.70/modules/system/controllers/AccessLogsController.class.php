<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('fw_access_logs', EnvironmentFramework::NAME);

/**
 * Application level access logs controller.
 *
 * @package activeCollab.modules.system
 * @subpackage controllers
 */
class AccessLogsController extends FwAccessLogsController
{
}
