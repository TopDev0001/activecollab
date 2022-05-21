<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', SystemModule::NAME);

class TeamsController extends AuthRequiredController
{
    /**
     * Selected team.
     *
     * @var Team
     */
    protected $active_team;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_team = DataObjectPool::get('Team', $request->getId('team_id'));

        if (empty($this->active_team)) {
            $this->active_team = new Team();
        }
    }

    /**
     * List all teams.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Teams::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * Create a new team instance.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return Teams::canAdd($user) ? Teams::create($request->post(), true) : Response::FORBIDDEN;
    }

    /**
     * View team.
     *
     * @return int|Team
     */
    public function view(Request $request, User $user)
    {
        return $this->active_team->isLoaded() && $this->active_team->canView($user) ? $this->active_team : Response::NOT_FOUND;
    }

    /**
     * Update a team.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_team->isLoaded() && $this->active_team->canEdit($user) ?
            Teams::update($this->active_team, $request->put(), true) :
            Response::NOT_FOUND;
    }

    /**
     * Delete a team.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_team->isLoaded() && $this->active_team->canDelete($user) ? $this->active_team->delete() : Response::NOT_FOUND;
    }
}
