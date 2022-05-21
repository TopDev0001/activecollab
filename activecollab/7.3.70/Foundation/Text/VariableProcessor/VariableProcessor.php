<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\VariableProcessor;

use ActiveCollab\Foundation\Text\VariableProcessor\Variable\VariableInterface;
use ActiveCollab\Foundation\Text\VariableProcessor\VariableProvider\VariableProviderInterface;

class VariableProcessor implements VariableProcessorInterface
{
    /**
     * @var VariableInterface[]
     */
    private array $variables = [];

    public function __construct(VariableProviderInterface ...$providers)
    {
        foreach ($providers as $provider) {
            foreach ($provider->getVariables() as $variable) {
                $this->variables[$variable->getName()] = $variable;
            }
        }
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function process(string $text): string
    {
        return preg_replace_callback(
            '/{([^}]*)}/',
            function ($matches) {
                [
                    $variable_name,
                    $num_modifier,
                ] = $this->processVariableName($matches[1]);

                return $this->replaceVariableWithValue($matches[0], $variable_name, $num_modifier);
            },
            $text
        );
    }

    private function processVariableName(string $variable_name): array
    {
        $num_modifier = 0;

        if (strpos($variable_name, '-')) {
            $bits = explode('-', $variable_name);

            if (ctype_digit($bits[1])) {
                $num_modifier = -1 * (int) $bits[1];
                $variable_name = $bits[0];
            }
        }

        if (strpos($variable_name, '+')) {
            $bits = explode('+', $variable_name);

            if (ctype_digit($bits[1])) {
                $num_modifier = (int) $bits[1];
                $variable_name = $bits[0];
            }
        }

        return [
            $variable_name,
            $num_modifier,
        ];
    }

    private function replaceVariableWithValue(
        string $matched_variable,
        string $variable_name,
        int $num_modifier
    ): string
    {
        if (empty($this->variables[$variable_name])) {
            return $matched_variable;
        }

        return $this->variables[$variable_name]->process($num_modifier);
    }
}
