<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

function system_handle_on_reset_manager_states()
{
    UserInvitations::resetState();
    UserWorkspaces::resetState();
    Users::resetState();
    Comments::resetState();
}
