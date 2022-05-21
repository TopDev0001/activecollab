<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\ExecuteCliCommand;

use ActiveCollab\JobsQueue\Signals\SignalInterface;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

trait ExecuteCliCommandTrait
{
    /**
     * Validate and prepare CLI command data.
     *
     * @param array $data
     */
    protected function validateAndPrepareCommandData(array &$data)
    {
        if (empty($data['command'])) {
            throw new InvalidArgumentException("'command' property is required");
        }

        if (empty($data['command_arguments'])) {
            $data['command_arguments'] = [];
        }

        if (empty($data['command_options'])) {
            $data['command_options'] = [];
        }

        if (empty($data['in_background'])) {
            $data['in_background'] = false;
        }

        if ($data['in_background'] && DIRECTORY_SEPARATOR == '\\') {
            throw new LogicException('Background jobs are not supported on Windows');
        }

        if (empty($data['log_output_to_file'])) {
            $data['log_output_to_file'] = '';
        }
    }

    /**
     * Run command.
     *
     * @param  string                 $command
     * @param  string|null            $from_working_directory
     * @return SignalInterface|string
     */
    protected function runCommand($command, $from_working_directory = null)
    {
        // Check working directory if $from_working_directory is set and not current directory
        if ($from_working_directory) {
            $old_working_directory = getcwd();

            if ($old_working_directory != $from_working_directory) {
                if (!chdir($from_working_directory)) {
                    throw new RuntimeException("Failed to change working directory to '$from_working_directory'");
                }
            }
        }

        $log_to_file = $this->getData()['log_output_to_file'];

        if (!empty($log_to_file)) {
            $log_to_file = escapeshellarg($log_to_file);
        }

        $output = [];
        $code = 0;

        if ($this->getData()['in_background']) {
            if (empty($log_to_file)) {
                $log_to_file = '/dev/null';
            }

            $pid = 0;

            exec("nohup $command > $log_to_file 2>&1 & echo $!", $output, $code);

            if ($code === 0) {
                foreach ($output as $output_line) {
                    if (ctype_digit($output_line)) {
                        $pid = (integer) $output_line;
                        break;
                    }
                }
            }

            // Switch back to old working directory if we changed working directory
            if (isset($old_working_directory) && $old_working_directory != $from_working_directory) {
                chdir($old_working_directory);
            }

            if (!empty($pid)) {
                return $this->reportBackgroundProcess($pid);
            }
        } else {
            if (empty($log_to_file)) {
                $last_line = exec($command, $output, $code);
                print implode("\n", $output);
            } else {
                $last_line = exec("$command > $log_to_file", $output, $code);
            }

            if (isset($old_working_directory) && $old_working_directory != $from_working_directory) {
                chdir($old_working_directory); // Switch back to old working directory if we changed working directory
            }

            // Switch back to old working directory if we changed working directory
            if ($code !== 0) {
                throw new RuntimeException("Command exited with error #{$code}", $code);
            }

            return $last_line;
        }

        return null;
    }

    public function prepareCommandFromData(): string
    {
        $command = $this->getData('command');

        foreach ($this->getData('command_arguments') as $v) {
            $command .= ' ' . escapeshellarg($v);
        }

        foreach ($this->getData('command_options') as $k => $v) {
            if (is_bool($v)) {
                if ($v) {
                    $command .= " --{$k}";
                }
            } else {
                $command .= " --{$k}=" . escapeshellarg((is_array($v) ? implode(',', $v) : $v));
            }
        }

        return $command;
    }

    abstract public function getData($property = null);
    abstract protected function reportBackgroundProcess($process_id);
}
