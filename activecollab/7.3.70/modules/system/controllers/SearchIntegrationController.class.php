<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\TestConnectionStatus\TestConnectionStatusInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('integration_singletons', EnvironmentFramework::INJECT_INTO);

/**
 * @property SearchIntegration $active_integration
 */
final class SearchIntegrationController extends IntegrationSingletonsController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$this->active_integration instanceof SearchIntegration) {
            return Response::CONFLICT;
        }
    }

    /**
     * @return SearchIntegration|StatusResponse|int
     */
    public function configure(Request $request, User $user)
    {
        if (!$this->active_integration->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        try {
            return $this->active_integration->configure($request->post());
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Configure ElasticSearch failed with error: {message}',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return new StatusResponse(
                400,
                'Failed to configure ElasticSearch.'
            );
        }
    }

    /**
     * @return array|int
     */
    public function test_connection(Request $request)
    {
        $success_message = null;
        $error_message = null;

        try {
            $post = $request->post();

            if (empty($post['hosts']) || trim($post['hosts']) === '') {
                return Response::BAD_REQUEST;
            }

            $test = $this->active_integration->testConnection(explode(',', $post['hosts']));

            if ($test instanceof TestConnectionStatusInterface) {
                if ($test->isSuccess()) {
                    $success_message = $test->getClusterInfo();
                } else {
                    $error_message = $test->getFailureReason();
                }
            } else {
                throw new RuntimeException('Test connection did not return status information.');
            }
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to test ElasticSearch connection due to an exception.',
                [
                    'exception' => $e,
                ]
            );

            $error_message = $e->getMessage();
        }

        return [
            'ok' => empty($error_message),
            'success' => (string) $success_message,
            'error' => (string) $error_message,
        ];
    }

    /**
     * @return SearchIntegration|mixed
     */
    public function disconnect(Request $request)
    {
        try {
            return $this->active_integration->disconnect();
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to disconnect from ElasticSearch configuration.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return new StatusResponse(
                400,
                'Failed to disconnect from ElasticSearch configuration.'
            );
        }
    }
}
