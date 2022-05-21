<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;

/**
 * Integration class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
interface IntegrationInterface extends RoutingContextInterface
{
    /**
     * Return integration object ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get group of this integration.
     *
     * @return string
     */
    public function getGroup();

    /**
     * Get group order of this integration.
     *
     * @return int|null
     */
    public function getGroupOrder();

    public function isInUse(User $user = null): bool;

    /**
     * Return webhooks created and owned by this integration.
     *
     * @return Webhook[]|null
     */
    public function getWebhooks();

    /**
     * Returns true if this integration is provided by a third party.
     *
     * @return bool
     */
    public function isThirdParty();

    /**
     * Return true if this integration is available for self-hosted packages.
     *
     * @return bool
     */
    public function isAvailableForSelfHosted();

    /**
     * Return true if this integration is available for on-demand packages.
     *
     * @return bool
     */
    public function isAvailableForOnDemand();

    public function isSingleton(): bool;
    public function getShortName(): string;

    public function isVisible(): bool;
}
