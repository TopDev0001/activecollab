<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class FwThirdPartyIntegration extends Integration
{
    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        $client_vendor = $this->getClientVendor();
        $client_name = $this->getClientName();

        return $client_vendor
            && $client_name
            && DB::executeFirstCell(
                'SELECT COUNT(id) AS "row_count" FROM api_subscriptions WHERE client_vendor = ? AND client_name = ?',
                $client_vendor,
                $client_name
            );
    }

    public function isThirdParty()
    {
        return true;
    }

    /**
     * Return name that of the organization that is producting this client.
     *
     * @return string
     */
    protected function getClientVendor()
    {
        return '';
    }

    /**
     * Return name that third party uses to idenfity this client.
     *
     * @return string
     */
    protected function getClientName()
    {
        return '';
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Dropping third party integration');

            parent::delete($bulk);

            $client_vendor = $this->getClientVendor();
            $client_name = $this->getClientName();

            if ($client_vendor && $client_name) {
                DB::executeFirstCell('DELETE FROM api_subscriptions WHERE client_vendor = ? AND client_name = ?', $client_vendor, $client_name);
            }

            DB::commit('Third party integration instance dropped');
        } catch (Exception $e) {
            DB::rollback('Failed to drop third party integration instance');
            throw $e;
        }
    }
}
