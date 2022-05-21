<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor;

use ActiveCollab\Foundation\Text\VariableProcessor\Variable\VariableInterface;

interface VariableProcessorInterface
{
    /**
     * @return VariableInterface[]
     */
    public function getVariables(): array;
    public function process(string $text): string;
}
