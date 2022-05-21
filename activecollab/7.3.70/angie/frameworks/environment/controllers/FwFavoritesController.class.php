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
 * Favorites controller.
 *
 * @package angie.frameworks.favorites
 * @subpackage controllers
 */
abstract class FwFavoritesController extends AuthRequiredController
{
    /**
     * Selected object, that we'll add to or remove from favorites.
     *
     * @var ApplicationObject
     */
    protected $active_object;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $parent_type = $request->get('parent_type');

        if ($parent_type) {
            $parent_type = Angie\Inflector::camelize(str_replace('-', '_', $parent_type));
        }

        $parent_id = $request->getId('parent_id');

        if ($parent_type && $parent_id) {
            if (class_exists($parent_type) && is_subclass_of($parent_type, 'DataObject')) {
                $this->active_object = DataObjectPool::get($parent_type, $parent_id);
            }

            if (!($this->active_object instanceof IFavorite && method_exists($this->active_object, 'canView') && $this->active_object->canView($user))) {
                return Response::NOT_FOUND;
            }
        }
    }

    /**
     * List favorite objects.
     *
     * @return ApplicationObject[]
     */
    public function index(Request $request, User $user)
    {
        $result = [];

        if ($favorites = Favorites::findFavoriteObjectsByUser($user)) {
            foreach ($favorites as $favorite) {
                $result[] = [get_class($favorite), $favorite->getId(), $favorite instanceof ITrash && $favorite->getIsTrashed()];
            }
        }

        return $result;
    }

    /**
     * Check if object is favorited by the user.
     *
     * @return int
     */
    public function check(Request $request, User $user)
    {
        return Favorites::isFavorite($this->active_object, $user) ? Response::OK : Response::NOT_FOUND;
    }

    /**
     * Add selected object to favorites.
     *
     * @return int|void
     */
    public function add(Request $request, User $user)
    {
        Favorites::addToFavorites($this->active_object, $user);

        return $this->active_object;
    }

    /**
     * Remove selected objects from favorites.
     *
     * @return int|void
     */
    public function remove(Request $request, User $user)
    {
        Favorites::removeFromFavorites($this->active_object, $user);

        return Response::OK;
    }
}
