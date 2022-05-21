<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchResult\Hit;

use DataObject;

final class SearchResultHit implements SearchResultHitInterface
{
    private $hit;
    private $score;
    private $name_highlights;
    private $body_highlights;

    /**
     * SearchResultHit constructor.
     *
     * @param float $score
     */
    public function __construct(
        DataObject $hit,
        $score,
        array $name_highlights,
        array $body_highlights
    )
    {
        $this->hit = $hit;
        $this->score = $score;
        $this->name_highlights = $name_highlights;
        $this->body_highlights = $body_highlights;
    }

    /**
     * @return DataObject
     */
    public function getHit()
    {
        return $this->hit;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return array
     */
    public function getNameHighlights()
    {
        return $this->name_highlights;
    }

    /**
     * @return array
     */
    public function getBodyHighlights()
    {
        return $this->body_highlights;
    }

    public function jsonSerialize(): array
    {
        $highlight = [];

        if (!empty($this->name_highlights)) {
            $highlight['name'] = $this->name_highlights;
        }

        if (!empty($this->body_highlights)) {
            $highlight['body'] = $this->body_highlights;
        }

        return [
            'hit' => $this->hit,
            'score' => $this->score,
            'highlight' => $highlight,
        ];
    }
}
