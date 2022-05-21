<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls;

use ActiveCollab\Foundation\Urls\ModalArguments\ModalArgumentsInterface;

class InternalUrl extends Url implements InternalUrlInterface
{
    private ?ModalArgumentsInterface $modal_arguments;
    private bool $is_modal;

    public function __construct(string $url, ModalArgumentsInterface $modal_arguments = null)
    {
        parent::__construct($url);

        $this->modal_arguments = $modal_arguments;
        $this->is_modal = !empty($modal_arguments);
    }

    public function getModalArguments(): ?ModalArgumentsInterface
    {
        return $this->modal_arguments;
    }

    public function isModal(): bool
    {
        return $this->is_modal;
    }
}
