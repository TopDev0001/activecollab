<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

/**
 * Member/Employee user role implementation.
 *
 * @package activeCollab.modules.system
 * @subpackage models
 */
class Member extends User
{
    /**
     * Return true if this member is an empoyee of the owner company.
     *
     * @return bool
     */
    public function isEmployee()
    {
        return $this->getCompanyId() == AngieApplication::getContainer()
                ->get(OwnerCompanyResolverInterface::class)
                    ->getId();
    }

    public function getAvailableCustomPermissions(): array
    {
        $custom_permissions = parent::getAvailableCustomPermissions();

        if ($this->isMember(true)) {
            $custom_permissions = array_merge(
                $custom_permissions,
                [
                    User::CAN_MANAGE_PROJECTS,
                    User::CAN_MANAGE_FINANCES,
                ]
            );
        }

        return $custom_permissions;
    }

    /**
     * All members can use trash.
     *
     * @return bool
     */
    public function canUseTrash()
    {
        return true;
    }
}
