<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls;

use ActiveCollab\Foundation\Urls\ModalArguments\ModalArgumentsInterface;

interface InternalUrlInterface extends UrlInterface
{
    public function getModalArguments(): ?ModalArgumentsInterface;
    public function isModal(): bool;
}
