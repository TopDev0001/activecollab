<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('integration_singletons', SystemModule::NAME);

class WebhooksIntegrationController extends IntegrationSingletonsController
{
    /**
     * @var Webhook
     */
    protected $webhook;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->webhook = DataObjectPool::get('Webhook', $request->getId('webhook_id'));
        if (empty($this->webhook)) {
            $this->webhook = new Webhook();
        }
    }

    /**
     * Create a webhook entry.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return Webhooks::canAdd($user) ? Webhooks::create($request->post()) : Response::FORBIDDEN;
    }

    /**
     * Edit existing webhook or create new.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return Webhooks::canEdit($user) ? Webhooks::update($this->webhook, $request->put()) : Response::FORBIDDEN;
    }

    /**
     * Edit existing webhook or create new.
     *
     * @return DataObject|int
     */
    public function delete(Request $request, User $user)
    {
        return Webhooks::canDelete($user) ? $this->webhook->delete() : Response::FORBIDDEN;
    }
}
