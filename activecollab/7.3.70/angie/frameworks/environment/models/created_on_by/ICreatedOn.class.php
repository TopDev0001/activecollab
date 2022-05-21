<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Created on interface definition.
 *
 * @package angie.framework.environment
 * @subpackage models
 */
interface ICreatedOn
{
    /**
     * Return value of created_on field.
     *
     * @return DateTimeValue
     */
    public function getCreatedOn();

    /**
     * Set value of created_on field.
     *
     * @param  DateTimeValue $value
     * @return DateTimeValue
     */
    public function setCreatedOn($value);
}
