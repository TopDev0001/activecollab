<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('selected_object', EnvironmentFramework::INJECT_INTO);

abstract class FwSubscribersController extends SelectedObjectController
{
    /**
     * Selected object.
     *
     * @var DataObject|ISubscriptions
     */
    protected $active_object;

    protected string $active_object_instance_of = 'ISubscriptions';

    /**
     * List subscriptions.
     */
    public function index()
    {
        return $this->active_object->getSubscribersAsArray();
    }

    /**
     * Replace current list of subscribers with new list of subscribers.
     *
     * @return array
     */
    public function bulk_update(Request $request, User $user)
    {
        if ($this->active_object->canEdit($user)) {
            return $this->active_object->setSubscribers($this->subscribersFromRequest($request->post(), $user));
        }

        return Response::NOT_FOUND;
    }

    /**
     * Subscribe selected user to selected object.
     *
     * @return array
     */
    public function bulk_subscribe(Request $request, User $user)
    {
        if ($this->active_object->canEdit($user)) {
            $new_subscribers = $this->subscribersFromRequest($request->post(), $user);

            if (count($new_subscribers)) {
                $this->active_object->setSubscribers($new_subscribers, false);
            }

            return $this->active_object->getSubscribersAsArray();
        }

        return Response::NOT_FOUND;
    }

    /**
     * Bulk unsubscribe.
     *
     * @return array
     */
    public function bulk_unsubscribe()
    {
        $this->active_object->clearSubscribers();

        return $this->active_object->getSubscribersAsArray();
    }

    /**
     * Subscribe individual.
     *
     * @return int
     */
    public function subscribe(Request $request, User $user)
    {
        /** @var User $user_to_subscribe */
        $user_to_subscribe = DataObjectPool::get('User', $request->get('user_id'));

        if ($user_to_subscribe) {
            if ($this->active_object->canView($user_to_subscribe)) {
                $this->active_object->subscribe($user_to_subscribe);

                return $this->active_object->getSubscribersAsArray();
            }

            return Response::FORBIDDEN;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Unsubscribe individiaul.
     *
     * @return int
     */
    public function unsubscribe(Request $request, User $user)
    {
        /** @var User $user_to_unsubscribe */
        if ($user_to_unsubscribe = DataObjectPool::get('User', $request->get('user_id'))) {
            $this->active_object->unsubscribe($user_to_unsubscribe);

            return $this->active_object->getSubscribersAsArray();
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Get subscribers from request.
     *
     * @param  array                  $input
     * @return User[]|AnonymousUser[]
     */
    private function subscribersFromRequest($input, User $user)
    {
        // Person who can edit the objet can subscribe anyone
        if ($this->active_object->canEdit($user)) {
            $user_ids = [];
            $anonymous_users = [];

            foreach ($input as $to_subscribe) {
                if (is_numeric($to_subscribe)) {
                    $user_ids[] = (int) $to_subscribe;
                } elseif (is_string($to_subscribe) && is_valid_email($to_subscribe)) {
                    $anonymous_users[] = new AnonymousUser('', $to_subscribe);
                } elseif (is_array($to_subscribe) && count($to_subscribe) == 2 && is_valid_email($to_subscribe[1])) {
                    $anonymous_users[] = new AnonymousUser($to_subscribe[0], $to_subscribe[1]);
                }
            }

            $new_subscribers = count($user_ids) ? Users::findByIds($user_ids) : [];

            if ($new_subscribers instanceof DBResult) {
                $new_subscribers = $new_subscribers->toArray();
            }

            if (count($anonymous_users)) {
                $new_subscribers = array_merge($new_subscribers, $anonymous_users);
            }

            return $new_subscribers;

            // People who can only see the object can only subscribe themselves
        }

        return in_array($user->getId(), $input) ? [$user] : [];
    }
}
