<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

AngieApplication::useController('fw_auth_not_required', EnvironmentFramework::NAME);

class AuthNotRequiredController extends FwAuthNotRequiredController
{
}
