<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_not_required', EnvironmentFramework::INJECT_INTO);

/**
 * Languages controller.
 *
 * @package angie.frameworks.globalization
 * @subpackage controllers
 */
abstract class FwLanguagesController extends AuthNotRequiredController
{
    /**
     * @var Language
     */
    protected $active_language;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_language = DataObjectPool::get('Language', $request->getId('language_id'));
        if (empty($this->active_language)) {
            $this->active_language = new Language();
        }
    }

    /**
     * Show main languages page.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Languages::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * View language details.
     *
     * @return Language|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_language->isLoaded() && $this->active_language->canView($user) ? $this->active_language : Response::NOT_FOUND;
    }

    /**
     * @return Language|int
     */
    public function view_default()
    {
        if ($language = DataObjectPool::get('Language', Languages::getDefaultId())) {
            return $language;
        }

        return Response::NOT_FOUND;
    }

    /**
     * @return Language|int
     */
    public function set_default(Request $request, User $user)
    {
        if (!$user->isOwner()) {
            return Response::NOT_FOUND;
        }

        /** @var Language $language */
        if ($language = DataObjectPool::get('Language', $request->put('language_id'))) {
            return Languages::setDefault($language);
        }

        return Response::BAD_REQUEST;
    }
}
