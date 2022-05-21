<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Application level integrations controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class IntegrationsController extends AuthRequiredController
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

        $this->active_integration = DataObjectPool::get(
            Integration::class,
            $request->getId('integration_id')
        );

        if ($this->active_integration instanceof Integration && !$this->active_integration->canView($user)) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * Add new integration.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return $user->isOwner() ? Integrations::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * Return integrations that the given user can see.
     *
     * @return array
     */
    public function index(Request $request, User $user)
    {
        return Integrations::getFor($user);
    }

    /**
     * Return selected integration.
     */
    public function view()
    {
        return $this->active_integration instanceof Integration ? $this->active_integration : Response::NOT_FOUND;
    }

    /**
     * Edit integration.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_integration instanceof Integration && $this->active_integration->canEdit($user)
            ? Integrations::update($this->active_integration, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Drop the given integration.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_integration instanceof Integration && $this->active_integration->canDelete($user)
            ? DataFilters::scrap($this->active_integration)
            : Response::NOT_FOUND;
    }
}
