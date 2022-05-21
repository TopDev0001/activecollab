<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor\Variable;

interface VariableInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function supportsNumModifier(): bool;
    public function process(int $num_modifier): string;
}
