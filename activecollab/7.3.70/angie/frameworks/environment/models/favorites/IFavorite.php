<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Indicator that object can be added to favorites.
 *
 * @package angie.frameworks.favorites
 * @subpackage models
 */
interface IFavorite
{
    /**
     * Return parent object ID.
     *
     * @return int
     */
    public function getId();
}
