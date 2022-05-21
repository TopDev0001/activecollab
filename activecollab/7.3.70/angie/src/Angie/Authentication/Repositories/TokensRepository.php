<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\Repositories;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\Token\RepositoryInterface;
use ActiveCollab\Authentication\Token\TokenInterface;
use Angie\Authentication\Exception\AuthenticationException;
use AngieApplication;
use ApiSubscription;
use ApiSubscriptions;
use DateTimeInterface;
use DateTimeValue;
use DB;
use Exception;
use InvalidArgumentException;
use User;

/**
 * @package Angie\Authentication\Repositories
 */
class TokensRepository implements RepositoryInterface
{
    public function getById($token_id)
    {
        return ApiSubscriptions::findOneBy('token_id', $token_id);
    }

    public function getUsageById($token_id)
    {
        return (int) DB::executeFirstCell('SELECT requests_count FROM api_subscriptions WHERE token_id = ?', $token_id);
    }

    public function getUsageByToken(TokenInterface $token)
    {
        if ($token instanceof ApiSubscription) {
            return $token->getRequestsCount();
        }

        throw new InvalidArgumentException('Invalid token instance. API subscription expected');
    }

    public function recordUsageById($token_id)
    {
        if ($id = DB::executeFirstCell('SELECT id FROM api_subscriptions WHERE token_id = ?', $token_id)) {
            DB::executeFirstCell('UPDATE api_subscriptions SET requests_count = requests_count + 1, last_used_on = ? WHERE id = ?', new DateTimeValue(), $id);
            AngieApplication::cache()->removeByObject([ApiSubscription::class, $id]);
        }
    }

    public function recordUsageByToken(TokenInterface $token)
    {
        if ($token instanceof ApiSubscription) {
            $token->setLastUsedOn(new DateTimeValue());
            $token->setRequestsCount($token->getRequestsCount() + 1);
            $token->save();
        } else {
            throw new InvalidArgumentException('Invalid token instance. API subscription expected');
        }
    }

    public function issueToken(AuthenticatedUserInterface $user, array $credentials = [], DateTimeInterface $expires_at = null)
    {
        if ($user instanceof User) {
            if (!$user->isActive()) {
                throw new AuthenticationException(AuthenticationException::FAILED_TO_ISSUE_TOKEN);
            }

            $client_vendor = isset($credentials['client_vendor']) ? trim($credentials['client_vendor']) : '';
            $client_name = isset($credentials['client_name']) ? trim($credentials['client_name']) : '';

            if (empty($client_vendor) || empty($client_name)) {
                throw new InvalidArgumentException('Client details are missing');
            }

            $token = ApiSubscriptions::findOne(
                [
                    'conditions' => [
                        'user_id = ? AND client_vendor = ? AND client_name = ?',
                        $user->getId(),
                        $client_vendor,
                        $client_name,
                    ],
                ]
            );

            if ($token) {
                return $token;
            }

            try {
                return ApiSubscriptions::create(
                    [
                        'user_id' => $user->getId(),
                        'client_vendor' => $client_vendor,
                        'client_name' => $client_name,
                        'created_on' => DateTimeValue::now(),
                        'last_used_on' => DateTimeValue::now(),
                    ]
                );
            } catch (Exception $e) {
                throw new AuthenticationException(AuthenticationException::FAILED_TO_ISSUE_TOKEN, null, $e);
            }
        } else {
            throw new InvalidArgumentException('Invalid user instance');
        }
    }

    public function terminateToken(TokenInterface $token)
    {
        if ($token instanceof ApiSubscription) {
            $token->delete();
        } else {
            throw new InvalidArgumentException('Invalid token instance. API subscription expected');
        }
    }
}
