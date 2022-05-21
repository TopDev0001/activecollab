<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('integration_singletons', EmailFramework::INJECT_INTO);

use ActiveCollab\ActiveCollabJobs\Jobs\Imap\TestConnection as ImapConnectionTest;
use ActiveCollab\ActiveCollabJobs\Jobs\Smtp\TestConnection as SmtpConnectionTest;
use Angie\Http\Request;
use Angie\Http\Response;

/**
 * Email integrations controller.
 *
 * @package angie.frameworks.email
 * @subpackage controllers
 */
class FwEmailIntegrationController extends IntegrationSingletonsController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!($this->active_integration instanceof EmailIntegration)) {
            return Response::CONFLICT;
        }

        return null;
    }

    /**
     * @return array|int|DbResult
     */
    public function email_log()
    {
        if (AngieApplication::isOnDemand()) {
            return Response::NOT_FOUND;
        }

        if ($email_log = DB::execute('SELECT sender, recipient, subject, sent_on FROM email_log WHERE sent_on > ? ORDER BY sent_on DESC', DateTimeValue::makeFromString('-14 days'))) {
            $email_log->setCasting('sent_on', DBResult::CAST_DATETIME);

            return $email_log;
        }

        return [];
    }

    /**
     * @return array
     */
    public function test_connection(Request $request)
    {
        $post_data = $request->post();

        $test_job = $this->getTestInstanceFromPost($post_data);

        $response = [
            'ok' => '',
            'debug' => '',
            'message' => '',
        ];

        try {
            $test = AngieApplication::jobs()->execute($test_job);

            $response['message'] = $test['message'];
            $response['debug'] = $test['debug'];
            $response['isOK'] = $test['isOk'];
        } catch (Exception $e) {
            $response['isOK'] = false;
            $response['message'] = $e->getMessage();
        }

        if (AngieApplication::isInDebugMode()) {
            $this->logSmtpDebug($response, $post_data);
        }

        return [
            'ok' => (bool) $response['isOK'],
            'debug' => (string) $response['debug'],
            'error' => (string) $response['message'],
        ];
    }

    /**
     * Return test connection job by POST argument.
     *
     * @return ImapConnectionTest|SmtpConnectionTest
     * @throws InvalidParamError
     */
    private function getTestInstanceFromPost(array $post)
    {
        if (empty($post['connection_type']) || !in_array($post['connection_type'], ['smtp', 'imap'])) {
            throw new InvalidParamError(
                'connection_type',
                array_key_exists('connection_type', $post) ? $post['connection_type'] : null,
                'Connection type property is required'
            );
        }

        $post['instance_id'] = AngieApplication::getAccountId();

        $connection_type = $post['connection_type'];
        unset($post['connection_type']);

        return $connection_type === 'smtp'
            ? new SmtpConnectionTest($post)
            : new ImapConnectionTest($post);
    }

    /**
     * Log SMTP debug data to a file in case when debug is not valid UTF-8 string.
     *
     * @param array $response
     * @param array $post_data
     */
    private function logSmtpDebug($response, $post_data): void
    {
        if ($response['isOK'] && $post_data['connection_type'] !== 'smtp') {
            return;
        }

        $debug_data = (string) $response['debug'];

        if ($debug_data && !preg_match('//u', $debug_data)) {
            file_put_contents(
                ENVIRONMENT_PATH . '/logs/smtp-debug-log-' . date('Y-m-d-H-i-s') . '.txt',
                $debug_data
            );
        }
    }
}
