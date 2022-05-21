<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class WebhooksIntegration extends Integration
{
    const JOBS_QUEUE_CHANNEL = 'webhook';

    /**
     * Returns the name of the integration.
     *
     * @return string
     */
    public function getName()
    {
        return 'Webhooks';
    }

    public function getShortName(): string
    {
        return 'webhooks';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang("Notify 3rd party services about what's happening in ActiveCollab");
    }

    /**
     * Get group of this integration.
     *
     * @return string
     */
    public function getGroup()
    {
        return 'other';
    }

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return (bool) Webhooks::countEnabledForIntegration($this);
    }

    public function canView(User $user): bool
    {
        return $user instanceof Owner;
    }

    // ---------------------------------------------------
    //  Serialization
    // ---------------------------------------------------

    /**
     * Serialize object to json.
     */
    public function jsonSerialize(): array
    {
        $webhooks = Webhooks::prepareCollection('webhooks_integration', null)->execute();

        return array_merge(
            parent::jsonSerialize(),
            [
                'webhooks' => !empty($webhooks) ? $webhooks : [],
            ]
        );
    }
}
