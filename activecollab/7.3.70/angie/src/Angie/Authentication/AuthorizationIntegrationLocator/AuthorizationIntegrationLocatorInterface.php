<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\AuthorizationIntegrationLocator;

/**
 * @package Angie\Authentication\AuthorizationIntegrationLocator
 */
interface AuthorizationIntegrationLocatorInterface
{
    /**
     * @return \AuthorizationIntegrationInterface
     */
    public function getAuthorizationIntegration();
}
