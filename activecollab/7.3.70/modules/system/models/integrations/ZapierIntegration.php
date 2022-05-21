<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Http\SendRequest;
use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\Token\TokenInterface;
use ActiveCollab\JobsQueue\Jobs\Job;
use Angie\Authentication\Repositories\TokensRepository;

class ZapierIntegration extends Integration
{
    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        if ($user instanceof User) {
            $token = $this->getToken($user);

            if ($token instanceof TokenInterface) {
                return true;
            }
        }

        return false;
    }

    public function getName()
    {
        return 'Zapier';
    }

    public function getShortName(): string
    {
        return 'zapier';
    }

    public function getDescription()
    {
        return lang('Connect ActiveCollab to other apps.');
    }

    /**
     * Return api subscription.
     *
     * @return ApiSubscription|null
     */
    protected function getToken(AuthenticatedUserInterface $user)
    {
        return ApiSubscriptions::findOne(
            [
                'conditions' => [
                    'user_id = ? AND client_name = ? AND client_vendor = ?',
                    $user->getId(),
                    $this->getName(),
                    AngieApplication::getVendor(),
                ],
            ]
        );
    }

    /**
     * Enable integration.
     *
     * @return $this
     */
    public function enable(AuthenticatedUserInterface $user)
    {
        if (!$this->isInUse($user)) {
            (new TokensRepository())->issueToken(
                $user,
                [
                    'client_vendor' => AngieApplication::getVendor(),
                    'client_name' => $this->getName(),
                ]
            );
        } else {
            throw new RuntimeException('Zapier integration already enabled!');
        }

        return $this;
    }

    /**
     * Disable integration.
     *
     * @return $this
     */
    public function disable(AuthenticatedUserInterface $user)
    {
        $token = $this->getToken($user);

        try {
            DB::beginWork('Begin: removing api subscriptions and webhooks @ ' . __CLASS__);

            $token->delete();

            $webhooks = Webhooks::find(
                [
                    'conditions' => [
                        'created_by_id = ? AND integration_id = ?',
                        $user->getId(),
                        $this->getId(),
                    ],
                ]
            );

            if ($webhooks) {
                foreach ($webhooks as $webhook) {
                    // Add job to reverse unsubscribe zap
                    AngieApplication::jobs()->dispatch(
                        new SendRequest(
                            [
                                'priority' => Job::NOT_A_PRIORITY,
                                'instance_id' => AngieApplication::getAccountId(),
                                'url' => $webhook->getUrl(),
                                'method' => 'DELETE',
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                ],
                                'payload' => json_encode([]),
                            ]
                        ),
                        WebhooksIntegration::JOBS_QUEUE_CHANNEL
                    );

                    $webhook->delete();
                }
            }

            DB::commit('Done: removing api subscriptions and webhooks @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: removing api subscriptions and webhooks @ ' . __CLASS__);

            throw $e;
        }

        return $this;
    }

    /**
     * Return account url and user token.
     *
     * @return array
     */
    public function getDataForUser(User $user)
    {
        $subscription = $this->getToken($user);

        if (!$subscription instanceof ApiSubscription) {
            throw new Exception('Zapier integration is disabled!');
        }

        return [
            'account_url' => $subscription->getApiUrl(),
            'token' => $subscription->getFormattedToken(),
        ];
    }
}
