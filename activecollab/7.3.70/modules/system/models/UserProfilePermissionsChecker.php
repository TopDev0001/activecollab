<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.modules.system
 * @subpackage model
 */
class UserProfilePermissionsChecker
{
    private $user_who_change;

    private $user_which_change;

    private $is_self_hosted;

    private $is_on_demand;

    public function __construct(
        User $user_who_change,
        User $user_which_change,
        $is_on_demand
    ) {
        $this->user_who_change = $user_who_change;
        $this->user_which_change = $user_which_change;
        $this->is_self_hosted = !$is_on_demand;
        $this->is_on_demand = $is_on_demand;
    }

    /**
     * @return bool
     */
    public function canChangePassword()
    {
        if (AngieApplication::authentication()->getLoginPolicy()->isPasswordChangeEnabled()) {
            if ($this->user_which_change->getId() === $this->user_who_change->getId()) {
                return true;
            } elseif ($this->is_on_demand && $this->hasOneAccount()) {
                if ($this->user_which_change->isClient()) {
                    return $this->isOwnerOrInvitedBy();
                } elseif ($this->user_which_change->isMember(true) || $this->user_which_change->isOwner()) {
                    return $this->isOwnerOrInvitedBy() && $this->user_which_change->isPendingActivation();
                }
            }  elseif ($this->is_self_hosted) {
                if ($this->user_which_change->isClient()) {
                    return $this->isOwnerOrInvitedBy();
                } elseif ($this->user_which_change->isMember(true)) {
                    return $this->user_who_change->isOwner() ||
                        ($this->user_which_change->isPendingActivation() &&
                            $this->user_who_change->getId() === $this->user_which_change->getCreatedById());
                } elseif ($this->user_which_change->isOwner()) {
                    return $this->user_who_change->isOwner();
                }
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function changeProfilePermissions()
    {
        $can_change_profile = $this->canChangeProfile();

        return [
            'can_change_profile' => $can_change_profile,
            'can_change_name' => !$can_change_profile ? $this->canChangeName() : true,
        ];
    }

    /**
     * @return bool
     */
    public function canChangeProfile()
    {
        if ($this->user_which_change->getId() === $this->user_who_change->getId()) {
            return true;
        } elseif ($this->is_on_demand && $this->hasOneAccount()) {
            if ($this->user_which_change->isClient()) {
                return $this->isOwnerOrInvitedBy();
            } elseif (($this->user_which_change->isMember(true) || $this->user_which_change->isOwner()) && $this->user_which_change->isPendingActivation()) {
                return $this->isOwnerOrInvitedBy();
            }
        } elseif ($this->is_self_hosted) {
            if ($this->user_which_change->isClient() || $this->user_which_change->isMember(true)) {
                return $this->isOwnerOrInvitedBy();
            } elseif ($this->user_which_change->isOwner()) {
                return false;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canChangeName()
    {
        return $this->isOwnerOrInvitedBy() && $this->isEmptyName(); // for both self-hosted and on demand is same condition
    }

    /**
     * Check is user who change owner or is user who invite.
     *
     * @return bool
     */
    private function isOwnerOrInvitedBy()
    {
        return $this->user_who_change->isOwner() || $this->user_who_change->getId() === $this->user_which_change->getCreatedById();
    }

    /**
     * Check is user which change has empty first or last name.
     *
     * @return bool
     */
    private function isEmptyName()
    {
        return empty($this->user_which_change->getFirstName()) || empty($this->user_which_change->getLastName());
    }

    /**
     * Check if user has one account.
     *
     * @return bool
     */
    private function hasOneAccount()
    {
        return UserWorkspaces::getWorkspaceCountForUser($this->user_which_change) === 1;
    }
}
