<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\AuthorizationIntegrationLocator;

use AuthorizationIntegration;
use AuthorizationIntegrationInterface;
use Integrations;
use InvalidArgumentException;
use LocalAuthorizationIntegration;
use LogicException;
use ReflectionClass;
use ShepherdAuthorizationIntegration;

class AuthorizationIntegrationLocator implements AuthorizationIntegrationLocatorInterface
{
    private $authorizer_integration_class_name;

    public function __construct(
        bool $is_on_demand,
        bool $is_in_dev = false,
        bool $is_in_test = false,
        bool $is_legacy_development = false,
        string $integration_class_name = ''
    ) {
        if ($is_in_test || ($is_in_dev && $is_legacy_development)) {
            $integration_class_name = LocalAuthorizationIntegration::class;
        } elseif (empty($integration_class_name)) {
            if ($is_on_demand) {
                $integration_class_name = ShepherdAuthorizationIntegration::class;
            } else {
                $integration_class_name = LocalAuthorizationIntegration::class;
            }
        }

        $this->authorizer_integration_class_name = $this->validateIntegrationClass(
            $is_on_demand,
            $is_in_dev || $is_in_test,
            $integration_class_name
        );
    }

    /**
     * @return string
     */
    public function getAuthorizerIntegrationClassName()
    {
        return $this->authorizer_integration_class_name;
    }

    public function getAuthorizationIntegration()
    {
        return Integrations::findFirstByType($this->getAuthorizerIntegrationClassName());
    }

    private function validateIntegrationClass(
        $is_on_demand,
        $is_in_dev_or_test,
        $integration_class_name
    ) {
        if (!class_exists($integration_class_name, true)) {
            throw new InvalidArgumentException('Authorization class does not exist');
        }

        $reflection_class = new ReflectionClass($integration_class_name);

        if (!$reflection_class->implementsInterface(AuthorizationIntegrationInterface::class)) {
            throw new InvalidArgumentException('Authorization class does not implement AuthorizationIntegrationInterface');
        }

        if (!$reflection_class->isSubclassOf(AuthorizationIntegration::class)) {
            throw new InvalidArgumentException('Authorization class does not extend AuthorizationIntegration');
        }

        if ($is_on_demand && !$is_in_dev_or_test && $integration_class_name === LocalAuthorizationIntegration::class) {
            throw new LogicException("Local authorization adapter can't be used when in On Demand mode");
        }

        return $integration_class_name;
    }
}
