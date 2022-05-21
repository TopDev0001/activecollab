<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Client implementation.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class Client extends User
{
    /**
     * Returns true if this user can manage company finances - receive and pay invoices, quotes etc.
     *
     * @return bool
     */
    public function canManageCompanyFinances()
    {
        return $this->getSystemPermission('can_manage_client_finances');
    }

    /**
     * Returns true if this user can request new projects.
     *
     * @return bool
     */
    public function canRequestProjects()
    {
        return $this->getSystemPermission('can_request_project');
    }

    public function getAvailableCustomPermissions(): array
    {
        $custom_permissions = parent::getAvailableCustomPermissions();

        if ($this->isClient(true) && Integrations::findFirstByType(ClientPlusIntegration::class)->isInUse()) {
            $custom_permissions = array_merge($custom_permissions, [User::CAN_MANAGE_TASKS]);
        }

        return $custom_permissions;
    }

    public function canSeeFieldHistory(IHistory $parent, string $field_name): bool
    {
        if ($parent instanceof Task && in_array($field_name, $this->getHiddenTaskFields())) {
            return false;
        }

        return parent::canSeeFieldHistory($parent, $field_name);
    }

    private function getHiddenTaskFields(): array
    {
        return [
            'estimate',
            'job_type_id',
        ];
    }
}
