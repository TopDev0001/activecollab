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

class SlackIntegrationController extends IntegrationSingletonsController
{
    /** @var SlackIntegration */
    protected $integration;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->integration = Integrations::findFirstByType('SlackIntegration', false);

        if (!($this->integration instanceof SlackIntegration)) {
            return Response::CONFLICT;
        }
    }

    /**
     * Connect integration with Slack.
     *
     * @return SlackIntegration
     */
    public function connect(Request $request)
    {
        $this->integration->setCode($request->put('code'));
        $channel = $this->integration->authorizeChannel($request->put('projects'));
        if ($channel) {
            return $channel;
        }

        return Response::OPERATION_FAILED;
    }

    /**
     * Edit notification channel.
     *
     * @return int|Webhook
     * @throws Exception
     */
    public function edit(Request $request, User $user)
    {
        if (Webhooks::canEdit($user)) {
            /** @var Webhook $slack_webhook */
            $slack_webhook = DataObjectPool::get('Webhook', $request->getId('notification_channel_id'));
            $slack_webhook->setFilterProjects($request->put());
            $slack_webhook->save();

            return $slack_webhook;
        }

        return Response::FORBIDDEN;
    }

    /**
     * Remove notification channel.
     *
     * @return int|void
     */
    public function delete(Request $request, User $user)
    {
        $slack_webhook = DataObjectPool::get('Webhook', $request->getId('notification_channel_id'));

        return Webhooks::canDelete($user) && !empty($slack_webhook) ? $slack_webhook->delete() : Response::FORBIDDEN;
    }
}
