<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Text\BodyProcessor\BodyProcessorInterface;

/**
 * Body interface.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
interface IBody
{
    /**
     * Return value of body field.
     *
     * @return string
     */
    public function getBody();

    /**
     * Set value of body field.
     *
     * @param  string $value
     * @return string
     */
    public function setBody($value);

    public function getNewMentions(): array;
    public function getFormattedBody(string $display = BodyProcessorInterface::DISPLAY_SCEEEN): string;
    public function getPlainTextBody(): string;
}
