<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface IHistory
{
    public function getHistory(User $user): array;
    public function getVerboseHistory(User $user): array;
    public function getHistoryFields(): array;
    public function addHistoryFields(string ...$field_names): void;
    public function getLatestModification(): ?ModificationLog;
}
