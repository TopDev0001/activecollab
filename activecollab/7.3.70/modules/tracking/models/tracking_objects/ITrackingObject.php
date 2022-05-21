<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Interface that all tracking objects implement.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
interface ITrackingObject
{
    const NOT_BILLABLE = 0;
    const BILLABLE = 1;
    const PENDING_PAYMENT = 2;
    const PAID = 3;

    const TRACKING_BILLABLE_STATUSES = [
        self::NOT_BILLABLE,
        self::BILLABLE,
        self::PENDING_PAYMENT,
        self::PAID,
    ];

    /**
     * Return record user.
     *
     * @return IUser
     */
    public function getUser();

    /**
     * Set record user.
     *
     * @return IUser
     */
    public function setUser(IUser $user);

    /**
     * Return value of user_id field.
     *
     * @return int
     */
    public function getUserId();

    /**
     * Set value of user_id field.
     *
     * @param  int $value
     * @return int
     */
    public function setUserId($value);

    /**
     * Return user name.
     *
     * @return string
     */
    public function getUserName();

    /**
     * Set value of user_name field.
     *
     * @param  string $value
     * @return string
     */
    public function setUserName($value);

    /**
     * Return user email address.
     *
     * @return string
     */
    public function getUserEmail();

    /**
     * Set value of user_email field.
     *
     * @param  string $value
     * @return string
     */
    public function setUserEmail($value);

    /**
     * Return record value.
     *
     * @return float
     */
    public function getValue();

    /**
     * Set value of value field.
     *
     * @param  float $value
     * @return float
     */
    public function setValue($value);

    /**
     * Return record date.
     *
     * @return DateValue
     */
    public function getRecordDate();

    /**
     * Set value of record_date field.
     *
     * @param  DateValue $value
     * @return DateValue
     */
    public function setRecordDate($value);

    /**
     * Return record summary.
     *
     * @return string
     */
    public function getSummary();

    /**
     * Set value of summary field.
     *
     * @param  string $value
     * @return string
     */
    public function setSummary($value);

    /**
     * Return object's billable status.
     *
     * @return int
     */
    public function getBillableStatus();

    /**
     * Set value of billable_status field.
     *
     * @param  int $value
     * @return int
     */
    public function setBillableStatus($value);
}
