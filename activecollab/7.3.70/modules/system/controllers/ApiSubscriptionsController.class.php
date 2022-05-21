<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('users', EnvironmentFramework::INJECT_INTO);

class ApiSubscriptionsController extends UsersController
{
    /**
     * Selected API client subscription.
     *
     * @var ApiSubscription
     */
    protected $active_api_subscription;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($this->active_user->isNew() || !$this->active_user->canManageApiSubscriptions($user)) {
            return Response::NOT_FOUND;
        }

        $this->active_api_subscription = DataObjectPool::get(
            ApiSubscription::class,
            $request->getId('api_subscription_id')
        );

        if (empty($this->active_api_subscription)) {
            $this->active_api_subscription = new ApiSubscription();
        }

        return null;
    }

    public function index(Request $request, User $user)
    {
        return ApiSubscriptions::prepareCollection(
            'api_subscriptions_for_' . $this->active_user->getId(),
            $user
        );
    }

    public function add(Request $request, User $user)
    {
        $post = $request->post();

        if (!is_array($post)) {
            $post = [];
        }

        $post['user_id'] = $this->active_user->getId();
        $post['is_enabled'] = true;

        if (isset($post['token'])) {
            unset($post['token']);
        }

        return  ApiSubscriptions::create($post);
    }

    public function view(Request $request, User $user)
    {
        return $this->active_api_subscription->isLoaded() ? $this->active_api_subscription : Response::NOT_FOUND;
    }

    public function edit(Request $request, User $user)
    {
        return Response::NOT_FOUND;
    }

    public function delete(Request $request, User $user)
    {
        return $this->active_api_subscription->isLoaded()
            ? ApiSubscriptions::scrap($this->active_api_subscription)
            : Response::NOT_FOUND;
    }
}
