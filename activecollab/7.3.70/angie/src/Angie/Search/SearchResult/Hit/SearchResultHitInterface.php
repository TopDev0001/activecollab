<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchResult\Hit;

use DataObject;
use JsonSerializable;

interface SearchResultHitInterface extends JsonSerializable
{
    /**
     * @return DataObject
     */
    public function getHit();

    /**
     * @return float
     */
    public function getScore();

    /**
     * @return array
     */
    public function getNameHighlights();

    /**
     * @return array
     */
    public function getBodyHighlights();
}
