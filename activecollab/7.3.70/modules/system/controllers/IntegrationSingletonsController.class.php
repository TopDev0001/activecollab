<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Inflector;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Application level integration singletons controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class IntegrationSingletonsController extends AuthRequiredController
{
    /**
     * Selected integration instance.
     *
     * @var Integration
     */
    protected $active_integration;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $integration_type = $this->getIntegrationTypeFromRequest($request);

        if ($this->isValidIntegrationType($integration_type)) {
            $this->active_integration = Integrations::findFirstByType($integration_type);

            if (!$this->active_integration->isSingleton() || !$this->active_integration->canView($user)) {
                return Response::NOT_FOUND;
            }

            if (AngieApplication::isOnDemand() && !$this->active_integration->isAvailableForOnDemand()) {
                return Response::NOT_FOUND;
            } else {
                if (!AngieApplication::isOnDemand() && !$this->active_integration->isAvailableForSelfHosted()) {
                    return Response::NOT_FOUND;
                }
            }
        } else {
            return Response::NOT_FOUND;
        }

        return null;
    }

    private function getIntegrationTypeFromRequest(Request $request): string
    {
        $integration_type = trim($request->get('integration_type'));

        if ($integration_type) {
            $integration_type = Inflector::camelize(
                    str_replace('-', '_', $integration_type)
                ) . 'Integration';
        }

        return $integration_type;
    }

    private function isValidIntegrationType(string $integration_type): bool
    {
        return $integration_type
            && class_exists($integration_type, true)
            && (new ReflectionClass($integration_type))->implementsInterface(IntegrationInterface::class);
    }

    /**
     * Return integration properties.
     *
     * @return Integration
     */
    public function get()
    {
        return $this->active_integration;
    }

    /**
     * Update integration properties.
     *
     * @return Integration|DataObject|int
     */
    public function set(Request $request, User $user)
    {
        return $this->active_integration->canEdit($user)
            ? Integrations::update($this->active_integration, $request->post())
            : Response::FORBIDDEN;
    }

    /**
     * Forget integration properties.
     *
     * @return bool|int
     */
    public function forget(Request $request, User $user)
    {
        return $this->active_integration->canDelete($user)
            ? Integrations::scrap($this->active_integration) : Response::FORBIDDEN;
    }
}
