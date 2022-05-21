<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Events;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Feedback controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class FeedbackController extends AuthRequiredController
{
    /**
     * Send feedback to the team.
     *
     * @return Notification|int
     */
    public function send(Request $request, User $user)
    {
        $comment = trim($request->post('comment'));

        if (empty($comment)) {
            return Response::BAD_REQUEST;
        }

        $details = $request->post('details');

        if (!is_array($details)) {
            $details = [];
        }

        if (!empty($details['sender_name'])) {
            $details['sender_name'] .= ' (' . get_class($user) . ')';
        } else {
            $details['user_role'] = get_class($user);
        }

        if (!empty($details['user_agent'])) {
            require_once APPLICATION_PATH . '/vendor/donatj/phpuseragentparser/Source/UserAgentParser.php';
            $details['user_agent'] = implode(' ', parse_user_agent($details['user_agent']));
        }

        if (AngieApplication::isOnDemand()) {
            $details['account_id'] = (int) AngieApplication::getAccountId();
            $details['account_status'] = AngieApplication::accountSettings()->getAccountStatus()->getVerboseStatus();
            $details['account_plan'] = AngieApplication::accountSettings()->getAccountPlan()->getName();

            if (AngieApplication::accountSettings()->getAccountStatus()->getStatusExpiresAt()) {
                $details['account_plan'] .= ' (' . AngieApplication::accountSettings()->getAccountStatus()->getStatusExpiresAt()->format('Y-m-d') . ')';
            }
        } else {
            $details['php_version'] = sprintf(
                '%d.%d.%d',
                PHP_MAJOR_VERSION,
                PHP_MINOR_VERSION,
                PHP_RELEASE_VERSION
            );
            $details['mysql_version'] = DB::getConnection()->getServerVersion();
            $details['license_key'] = strip_tags(trim(AngieApplication::getLicenseKey()));

            $status = $this->getSystemStatus();

            if (empty($status) || !is_array($status)) {
                $details['system_status'] = 'Failed to load';
            } else {
                foreach (['cron', 'search', 'email'] as $check) {
                    $is_ok_key = "{$check}_is_ok";

                    if (array_key_exists($is_ok_key, $status)) {
                        if ($status[$is_ok_key]) {
                            $details[$is_ok_key] = true;
                        } else {
                            $errors_key = "{$check}_errors";

                            if (array_key_exists($errors_key, $status) && is_array($status[$errors_key])) {
                                $details[$is_ok_key] = 'No. Errors: ' . implode(', ', $status[$errors_key]);
                            } else {
                                $details[$is_ok_key] = 'No. Error details are empty!';
                            }
                        }
                    }
                }
            }
        }

        /** @var FeedbackNotification $notification */
        $notification = AngieApplication::notifications()->notifyAbout('system/feedback', null, $user);

        return $notification
            ->setComment($comment)
            ->setDetails($details)
            ->sendToUsers(
                new AnonymousUser('ActiveCollab Support', 'support@activecollab.com'),
                true
            );
    }

    public function check(): array
    {
        if (AngieApplication::isOnDemand()) {
            return ['is_ok' => true, 'errors' => []];
        } else {
            $status = $this->getSystemStatus();

            return [
                'is_ok' => !empty($status['email_is_ok']),
                'errors' => !empty($status['email_errors']) ? $status['email_errors'] : [],
            ];
        }
    }

    private function getSystemStatus(): array
    {
        $status = [];
        Events::trigger('on_system_status', [&$status]);

        return $status;
    }
}
