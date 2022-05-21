<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('fw_subscribers', SubscriptionsFramework::NAME);

/**
 * Application level subscribers controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
final class SubscribersController extends FwSubscribersController
{
}
