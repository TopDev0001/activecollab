<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\UserInternalRateEvents\UserInternalRatePreDeletedEvent;

/**
 * UserInternalRate class.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
final class UserInternalRate extends BaseUserInternalRate
{
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'user_name' => $this->getUserName(),
            'user_email' => $this->getUserEmail(),
            'created_on' => $this->getCreatedOn(),
            'created_by_id' => $this->getCreatedById(),
            'created_by_email' => $this->getCreatedByEmail(),
            'created_by_name' => $this->getCreatedByName(),
            'valid_from' => $this->getValidFrom(),
            'rate' => $this->getRate(),
        ]);
    }

    public function delete($bulk = false)
    {
        AngieApplication::eventsDispatcher()->trigger(new UserInternalRatePreDeletedEvent($this));

        return parent::delete($bulk);
    }
}
