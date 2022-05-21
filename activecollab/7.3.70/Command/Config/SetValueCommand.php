<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Config;

use ActiveCollab\Foundation\Wrappers\ConfigOptions\ConfigOptionsInterface;
use AngieApplication;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetValueCommand extends ConfigCommand
{
    const CAST_STRING = 'string';
    const CAST_INT = 'int';
    const CAST_BOOL = 'bool';

    const CAST_TO = [
        self::CAST_STRING,
        self::CAST_INT,
        self::CAST_BOOL,
    ];

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Set configuration option value')
            ->addArgument(
                'option-name',
                InputArgument::REQUIRED,
                'Name of the option'
            )
            ->addArgument(
                'new-value',
                InputArgument::REQUIRED,
                'New value'
            )
            ->addOption(
                'cast-to',
                '',
                InputOption::VALUE_REQUIRED,
                'To which type should the value be cast'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $option_name = $input->getArgument('option-name');

            $config_options = AngieApplication::getContainer()->get(ConfigOptionsInterface::class);

            if (empty($option_name) || !$config_options->exists($option_name)) {
                throw new RuntimeException(sprintf('Option "%s" not found.', $option_name));
            }

            $this->showConfigOptionValue($option_name, $config_options, $output);

            if ($input->hasArgument('new-value')) {
                $new_value = $this->getValueToSet($input);

                $output->writeln('Setting the new value...');
                $config_options->setValue($option_name, $new_value);

                $this->showConfigOptionValue($option_name, $config_options, $output);
            }

            return 0;
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    private function showConfigOptionValue(
        string $option_name,
        ConfigOptionsInterface $config_options,
        OutputInterface $output
    ): void
    {
        $value = $config_options->getValue($option_name);

        $output->writeln(
            sprintf(
                'Current value: <comment>%s</comment> (<comment>%s</comment>)',
                var_export($value, true),
                gettype($value)
            )
        );
    }

    private function getValueToSet(InputInterface $input)
    {
        $new_value = (string) $input->getArgument('new-value');

        $cast_to = $input->getOption('cast-to');

        if (!empty($cast_to)) {
            switch ($cast_to) {
                case self::CAST_INT:
                    return (int) $new_value;
                case self::CAST_BOOL:
                    return (bool) $new_value;
                case self::CAST_STRING:
                    return $new_value;
                default:
                    throw new RuntimeException('Unknown cast type.');
            }
        }

        if (ctype_digit($new_value)) {
            $new_value = (int) $new_value;
        }

        return $new_value;
    }
}
