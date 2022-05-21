<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Authentication\Repositories\TokensRepository;

/**
 * ApiSubscriptions class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
final class ApiSubscriptions extends BaseApiSubscriptions
{
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'api_subscriptions_for')) {
            $bits = explode('_', $collection_name);
            $subscriber = DataObjectPool::get(User::class, array_pop($bits));

            if ($subscriber instanceof User && $subscriber->isActive()) {
                $collection = parent::prepareCollection($collection_name, $user);
                $collection->setConditions('user_id = ?', $subscriber->getId());

                return $collection;
            } else {
                throw new ImpossibleCollectionError('User not found or not active');
            }
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }
    }

    /**
     * Subscription error codes.
     */
    const ERROR_OPERATION_FAILED = 0;
    const ERROR_CLIENT_NOT_SET = 1;
    const ERROR_USER_DOES_NOT_EXIST = 2;
    const ERROR_INVALID_PASSWORD = 3;
    const ERROR_NOT_ALLOWED = 4;

    /**
     * Returns true if $user can create an API subscription.
     *
     * @return bool
     */
    public static function canSubscribe(User $user)
    {
        return $user instanceof User;
    }

    /**
     * Create a subscription for a known user.
     *
     * @param  string               $client_name
     * @param  string               $client_vendor
     * @return string
     * @throws ApiSubscriptionError
     */
    public static function subscribeKnownUser(User $user, $client_name, $client_vendor)
    {
        return (new TokensRepository())->issueToken($user, [
            'client_vendor' => $client_vendor,
            'client_name' => $client_name,
        ]);
    }

    /**
     * Generate token.
     *
     * @return string
     */
    public static function generateToken()
    {
        do {
            $token = make_string(40);
        } while (DB::executeFirstCell('SELECT COUNT(id) FROM api_subscriptions WHERE token_id = ?', $token) > 0);

        return $token;
    }

    /**
     * Drop expired subscriptions.
     */
    public static function deleteExpired()
    {
        self::delete(['last_used_on < ?', DateTimeValue::makeFromString('-1 year')]);
    }
}
