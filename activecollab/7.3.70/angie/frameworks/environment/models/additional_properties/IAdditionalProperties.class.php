<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface IAdditionalProperties
{
    /**
     * Return additional log properties as array.
     *
     * @return array
     */
    public function getAdditionalProperties();

    /**
     * Set attributes value.
     *
     * @param  mixed $value
     * @return mixed
     */
    public function setAdditionalProperties($value);

    /**
     * Returna attribute value.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getAdditionalProperty($name, $default = null);

    /**
     * Set attribute value.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return mixed
     */
    public function setAdditionalProperty($name, $value);
}
