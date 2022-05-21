<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class UserWorkspace extends BaseUserWorkspace
{
    public function jsonSerialize(): array
    {
        return array_merge(
            [
                'user_id' => $this->getUserId(),
                'shepherd_account_id' => $this->getShepherdAccountId(),
                'shepherd_account_type' => $this->getShepherdAccountType(),
                'shepherd_account_url' => $this->getShepherdAccountUrl(),
                'is_shown_in_launcher' => $this->getIsShownInLauncher(),
                'is_owner' => $this->getIsOwner(),
                'position' => $this->getPosition(),
            ],
            parent::jsonSerialize()
        );
    }
}
