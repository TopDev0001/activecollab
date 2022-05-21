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

class SocketAuthController extends AuthRequiredController
{
    /**
     * @var RealTimeIntegration
     */
    private $active_socket_integration;

    /**
     * @var string
     */
    private $channel_name;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_socket_integration = AngieApplication::realTimeIntegrationResolver()->getIntegration();

        if ($this->active_socket_integration === null) {
            AngieApplication::log()->debug('Try to auth on channel but socket integration not available');

            return Response::BAD_REQUEST;
        }

        $this->channel_name = (string) $request->post('channel_name', '');

        if (!$this->active_socket_integration->isValidChannel($this->channel_name, $user)) {
            AngieApplication::log()->error(
                "User #{user_id} in instance #{instance_id} can't authenticate on channel '{channel_name}'",
                [
                    'user_id' => AngieApplication::authentication()->getLoggedUserId(),
                    'instance_id' => AngieApplication::getAccountId(),
                    'channel_name' => $this->channel_name,
                ]
            );

            return Response::FORBIDDEN;
        }
    }

    /**
     * Authenticate user on socket channel.
     *
     * @return int|mixed
     */
    public function authenticate(Request $request, User $user)
    {
        try {
            return $this->active_socket_integration->authOnChannel(
                $this->channel_name,
                $request->post('socket_id', ''),
                $user,
                $this->prepareUserInfo($request)
            );
        } catch (Exception $e) {
            AngieApplication::log()->error('Authentication on channel failed with error: {message}', [
                'message' => $e->getMessage(),
                'instance_id' => AngieApplication::getAccountId(),
                'channel_name' => $this->channel_name,
            ]);

            return Response::FORBIDDEN;
        }
    }

    /**
     * Prepare dummi user's data.
     *
     * @return array
     */
    private function prepareUserInfo(Request $request)
    {
        $user_info = [];

        // private channel doesn't need dummi data
        if (str_starts_with($this->channel_name, RealTimeIntegrationInterface::SOCKET_CHANNEL_PRIVATE)) {
            return $user_info;
        }

        if ($user_agent = $request->getServerParam('HTTP_USER_AGENT')) {
            require_once APPLICATION_PATH . '/vendor/donatj/phpuseragentparser/Source/UserAgentParser.php';
            $values = parse_user_agent($user_agent);

            $user_info = [
                'platform' => $values['platform'],
                'browser' => $values['browser'],
            ];
        }

        return $user_info;
    }
}
