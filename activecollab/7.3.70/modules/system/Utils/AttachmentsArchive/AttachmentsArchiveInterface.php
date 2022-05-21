<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\AttachmentsArchive;

use JsonSerializable;

interface AttachmentsArchiveInterface extends JsonSerializable
{
    public function getArchiveId(): string;
    public function getPath(): string;
    public function prepareForDownload(): AttachmentsArchive;
}
