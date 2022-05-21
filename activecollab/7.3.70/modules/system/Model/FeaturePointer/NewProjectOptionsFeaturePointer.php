<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Model\FeaturePointer;

use FeaturePointer;
use User;

class NewProjectOptionsFeaturePointer extends FeaturePointer
{
    public function shouldShow(User $user): bool
    {
        return ($user->isOwner() || $user->isMember())
            && parent::shouldShow($user);
    }

    public function getDescription(): string
    {
        return lang('Now you can add recurring tasks and all the project essentials from one place!');
    }
}
