<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;
use Angie\Http\Response\StatusResponse\StatusResponseInterface;
use Angie\Inflector;

AngieApplication::useController('auth_required', SystemModule::NAME);

class ZapierWebhooksController extends AuthRequiredController
{
    /**
     * @var Webhook
     */
    protected $active_webhook;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_webhook = DataObjectPool::get(Webhook::class, $request->getId('zapier_webhook_id'));

        if (!$this->active_webhook instanceof Webhook) {
            $this->active_webhook = new Webhook();
        }
    }

    /**
     * Create zapier webhook.
     *
     * @return StatusResponseInterface|int
     */
    public function add(Request $request, User $user)
    {
        if (!Webhooks::canAdd($user) || $this->active_webhook->isLoaded()) {
            return Response::FORBIDDEN;
        }

        $target_url = $request->post('target_url');
        $event = $request->post('event');

        if (empty($target_url)) {
            return Response::BAD_REQUEST;
        }

        try {
            $webhook = Webhooks::create([
                'name' => 'Zapier Integration Webhook',
                'integration_type' => 'zapier_integration',
                'url' => $target_url,
                'is_enabled' => true,
                'filter_event_types' => implode(', ', [Inflector::camelize($event)]),
            ]);
        } catch (ValidationErrors $e) {
            return Response::CONFLICT;
        }

        // Return payload and status code Zapier request it!
        // Payload example {"id": 1234} json encoded
        // Status code '201 Created'
        return new StatusResponse(Response::CREATED, 'Created', [
            'id' => $webhook->getId(),
        ]);
    }

    /**
     * Delete zapier webhook.
     *
     * @return int
     */
    public function delete(Request $request, User $user)
    {
        if (!$this->active_webhook->isLoaded()) {
            return Response::NOT_FOUND;
        }

        if (!Webhooks::canDelete($user)) {
            return Response::FORBIDDEN;
        }

        return Webhooks::scrap($this->active_webhook);
    }
}
