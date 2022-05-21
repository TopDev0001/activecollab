<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ClientPlusIntegration extends Integration
{
    public function isSingleton(): bool
    {
        return true;
    }

    /**
     * Return integration name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Client+';
    }

    public function getShortName(): string
    {
        return 'client-plus';
    }

    /**
     * Return integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Allow clients to create and assign tasks');
    }

    public function isInUse(User $user = null): bool
    {
        return !empty($this->getAdditionalProperty('enabled'));
    }

    /**
     * Activate this integration.
     *
     * @return $this
     */
    public function enable()
    {
        $this->setAdditionalProperty('enabled', true);
        $this->save();

        AngieApplication::invalidateInitialSettingsCache();

        return $this;
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Delete Integration and revoke permissions @ ' . __CLASS__);

            // revoke custom permission to all client users
            if ($clients = Users::findByType(Client::class)) {
                /** @var Client[] $clients */
                foreach ($clients as $client) {
                    if ($client->canManageTasks()) {
                        Users::changeUserType(
                            $client,
                            $client->getType(),
                            [],
                            AngieApplication::authentication()->getLoggedUser()
                        );
                    }
                }
            }

            parent::delete($bulk);

            DB::commit('Integration deleted and permissions revoked @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to delete integration and revoke permissions @ ' . __CLASS__);
            throw $e;
        }

        AngieApplication::invalidateInitialSettingsCache();
    }

    public function jsonSerialize(): array
    {
        $client_plus_users = Users::findIdsByType(
            Client::class,
            [],
            function ($id, $type, $custom_permissions) {
                return in_array(User::CAN_MANAGE_TASKS, $custom_permissions);
            }
        );

        return array_merge(
            parent::jsonSerialize(),
            [
                'active_client_plus_users' => !empty($client_plus_users) ? count($client_plus_users) : 0,
            ]
        );
    }
}
