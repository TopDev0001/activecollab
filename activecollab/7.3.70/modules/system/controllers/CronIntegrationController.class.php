<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('fw_cron_integration', EnvironmentFramework::NAME);

/**
 * Application level Cron integrations controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class CronIntegrationController extends FwCronIntegrationController
{
}
