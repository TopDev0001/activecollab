<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface ILabels
{
    /**
     * @return Label[]|null
     */
    public function getLabels(): ?iterable;

    /**
     * @return int[]
     */
    public function getLabelIds(): array;
    public function getLabelNames(): array;
    public function countLabels(): int;
    public function clearLabels(): array;
    public function cloneLabelsTo(ILabels $to): ILabels;
    public function getLabelType(): string;
}
