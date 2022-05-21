<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Requirements for commentable objects.
 *
 * @package angie.frameworks.comments
 * @subpackage models
 */
interface IComments
{
    /**
     * Return code that will tell the application where to route replies to comments.
     *
     * @return string
     */
    public function getCommentRoutingCode();

    /**
     * Return comment submitted for this project object.
     *
     * @return Comment[]
     */
    public function getComments();

    /**
     * Returns true if parent object is read by the given user.
     *
     * @return bool
     */
    public function isRead(User $by);

    /**
     * Return $count of latest comments.
     *
     * @param  int      $count
     * @return DBResult
     */
    public function getLatestComments($count = 10);

    /**
     * Load more comments.
     *
     * @param  array    $loaded_comment_ids
     * @return DBResult
     */
    public function loadMoreComments($loaded_comment_ids, DateTimeValue $reference);

    /**
     * Return last comment by user.
     *
     * @return Comment
     */
    public function getLastComment();

    /**
     * Return number of comments for this particular object.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countComments($use_cache = true);

    /**
     * Return list of users involved in a discussion.
     *
     * @return AnonymousUser[]|User[]
     */
    public function getCommenters();

    // ---------------------------------------------------
    //  Utility methods
    // ---------------------------------------------------

    /**
     * Quickly create and submit a comment.
     *
     * @param  string  $body
     * @param  array   $additional
     * @param  bool    $log_access_for_parent
     * @return Comment
     */
    public function submitComment($body, IUser $by, $additional = null, $log_access_for_parent = false);

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true if this object allows anonymous comments.
     *
     * @return bool
     */
    public function allowAnonymousComments();

    /**
     * Returns true if $user can post a comment to this object.
     *
     * @return bool
     */
    public function canComment(IUser $user);

    /**
     * Return true if $user can send comments to the parent object via email.
     *
     * @return bool
     */
    public function canCommentViaEmail(IUser $user);

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return object ID.
     *
     * @return int
     */
    public function getId();

    public function canView(User $user): bool;
}
