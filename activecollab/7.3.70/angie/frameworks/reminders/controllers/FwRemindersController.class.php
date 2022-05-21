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
 * Application level reminders controller.
 *
 * @package angie.frameworks.reminders
 * @subpackage controllers
 */
abstract class FwRemindersController extends AuthRequiredController
{
    /**
     * Show reminders for a given object.
     *
     * @return ModelCollection|int
     */
    public function index(Request $request, User $user)
    {
        if ($parent = $this->parentFromGet($request, $user)) {
            return Reminders::prepareCollection('reminders_for_' . $user->getId() . '_in_' . get_class($parent) . '-' . $parent->getId(), $user);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Return parent from GET parameters.
     *
     * @return DataObject|IReminders|null
     */
    private function parentFromGet(Request $request, User $user)
    {
        $parent_type = $request->get('parent_type');

        if ($parent_type) {
            $parent_type = Angie\Inflector::camelize(str_replace('-', '_', $parent_type));
        }

        $parent_id = $request->getId('parent_id');

        if (class_exists($parent_type) && is_subclass_of($parent_type, 'DataObject')) {
            $parent = DataObjectPool::get($parent_type, $parent_id);

            if ($parent instanceof IReminders && $parent->canView($user)) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * @return Reminder|int
     */
    public function add(Request $request, User $user)
    {
        if ($parent = $this->parentFromGet($request, $user)) {
            $post = $request->post();

            if (empty($post['type'])) {
                $post['type'] = 'CustomReminder';
            }

            $post['parent_type'] = get_class($parent);
            $post['parent_id'] = $parent->getId();

            return Reminders::create($post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        $reminder = DataObjectPool::get('Reminder', $request->getId('reminder_id'));

        if ($reminder instanceof Reminder && $reminder->isLoaded() && $reminder->canDelete($user)) {
            return Reminders::remove($reminder);
        }

        return Response::NOT_FOUND;
    }
}
