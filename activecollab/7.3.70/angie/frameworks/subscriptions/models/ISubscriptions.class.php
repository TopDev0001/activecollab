<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Interface that all objects that have subscribers need to implement.
 *
 * @package angie.frameworks.subscriptions
 * @subpackage models
 */
interface ISubscriptions
{
    /**
     * Returns true if this object has people subscribed to it.
     */
    public function hasSubscribers(): bool;

    /**
     * Return number of people who are subscribed to this object.
     */
    public function countSubscribers(): int;

    /**
     * Returns subscribers as simple array.
     */
    public function getSubscribersAsArray(): array;

    /**
     * Return array of subscribed users.
     *
     * @return IUser[]|User[]
     */
    public function getSubscribers(): ?array;

    public function setSubscribers(
        ?iterable $users,
        bool $replace = true,
        bool $touch = true
    ): array;

    public function clearSubscribers(): void;

    /**
     * Return ID-s of subscribers.
     *
     * @return array
     */
    public function getSubscriberIds(): ?array;

    /**
     * Return subscription code for the given user.
     */
    public function getSubscriptionCodeFor(IUser $user): ?string;

    /**
     * Check if $user is subscribed to this object.
     */
    public function isSubscribed(IUser $user, bool $use_cache = true): bool;

    /**
     * Subscribe $user to this object.
     */
    public function subscribe(
        IUser $user,
        bool $bulk = false,
        bool $force = false
    ): void;

    /**
     * Unsubscribe $user from this object.
     */
    public function unsubscribe(IUser $user, bool $bulk = false): void;

    /**
     * Clone this object's subscriptions to a different object.
     *
     * @param array $limit_user_ids
     */
    public function cloneSubscribersTo(ISubscriptions $to, $limit_user_ids = []);

    /**
     * Returns true if $user can subscribe to this object.
     */
    public function canSubscribe(User $user): bool;

    public function canView(User $user): bool;
    public function canEdit(User $user): bool;
}
