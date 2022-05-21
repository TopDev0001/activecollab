<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Command;

use Angie\Error;
use Angie\Inflector;
use AngieApplication;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @package Angie\Command
 */
abstract class Command extends SymfonyCommand
{
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $output
            ->getFormatter()
            ->setStyle(
                'warn',
                new OutputFormatterStyle('red', null, ['bold'])
            );
    }

    protected function configure()
    {
        $bits = explode('\\', get_class($this));
        $command_class_name = Inflector::underscore(array_pop($bits));
        $command_name = $this->getCommandNamePrefix() . substr(Inflector::underscore($command_class_name), 0, strlen($command_class_name) - 8);

        $this->setName($command_name)
            ->addOption('debug', '', InputOption::VALUE_NONE, 'Output debug details')
            ->addOption('json', '', InputOption::VALUE_NONE, 'Output JSON');
    }

    protected function getCommandNamePrefix(): string
    {
        return '';
    }

    /**
     * @param string $message
     */
    protected function writeInfo($message, array $context, OutputInterface $output)
    {
        AngieApplication::log()->info($message, $context);
        $output->writeln('<info>OK</info>: ' . $this->prepareMessageForWrite($message, $context));
    }

    /**
     * @param string $message
     */
    protected function writeError($message, array $context, OutputInterface $output)
    {
        AngieApplication::log()->error($message, $context);
        $output->writeln('<error>Error</error>: ' . $this->prepareMessageForWrite($message, $context));
    }

    /**
     * @param  string $message
     * @return string
     */
    private function prepareMessageForWrite($message, array $context)
    {
        $to_write = $message;

        foreach ($context as $k => $v) {
            if (is_scalar($v)) {
                $to_write = str_replace('{' . $k . '}', "<comment>$v</comment>", $to_write);
            }
        }

        return $to_write;
    }

    /**
     * Abort due to error.
     *
     * @param  string $message
     * @param  int    $error_code
     * @return int
     */
    protected function abort($message, $error_code, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('json')) {
            $output->writeln(json_encode([
                'ok' => false,
                'error_message' => $message,
                'error_code' => $error_code,
            ]));
        } else {
            $output->writeln("<error>Error #{$error_code}:</error> " . $message);
        }

        return $error_code < 1 ? 1 : $error_code;
    }

    /**
     * Show success message.
     *
     * @param  string|mixed $message
     * @return int
     */
    protected function success($message, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('json')) {
            $output->writeln(
                json_encode(
                    [
                        'ok' => true,
                        'message' => $message,
                    ]
                )
            );
        } elseif ($message) {
            $output->writeln('<info>OK:</info> ' . $message);
        }

        return 0;
    }

    /**
     * Abort due to an exception.
     *
     * @return int
     */
    protected function abortDueToException(Throwable $e, InputInterface $input, OutputInterface $output)
    {
        $message = $e->getMessage();
        $code = $this->exceptionToErrorCode($e);

        if ($input->getOption('json')) {
            $response = [
                'ok' => false,
                'error_message' => $message,
                'error_code' => $code,
            ];

            if ($input->getOption('debug')) {
                $response['error_class'] = get_class($e);
                $response['error_file'] = $e->getFile();
                $response['error_line'] = $e->getLine();
                $response['error_trace'] = $e->getTraceAsString();
            }

            $output->writeln(json_encode($response));
        } else {
            if ($input->getOption('debug')) {
                $output->writeln("<error>Error #{$code}:</error> <" . get_class($e) . '> ' . $message . ', in file ' . $e->getFile() . ' on line ' . $e->getLine());
                $output->writeln('');

                if ($e instanceof Error) {
                    $output->writeln(var_export($e->getParams(), true));
                    $output->writeln('');
                }

                $output->writeln('Backtrace');
                $output->writeln('');
                $output->writeln($e->getTraceAsString());
            } else {
                $output->writeln("<error>Error #{$code}:</error> " . $message);
            }
        }

        return $code;
    }

    protected function exceptionToErrorCode(Throwable $e): int
    {
        return empty($e->getCode()) ? 1 : $e->getCode();
    }

    /**
     * Execute command, and return command's output.
     *
     * @param string $command
     */
    protected function executeCommand($command): array
    {
        $command_output = [];
        $command_exit_code = 0;

        exec($command, $command_output, $command_exit_code);

        if ($command_exit_code != 0) {
            throw new RuntimeException(
                sprintf(
                    'Command exited with error #%d: %s',
                    $command_exit_code,
                    implode("\n", $command_output)
                )
            );
        }

        return $command_output;
    }
}
