<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Launcher;

use AngieApplication;
use FileDnxError;
use InvalidParamError;
use NotImplementedError;

/**
 * Angie process launched delegate implementation.
 *
 * @package angie.library.application
 * @subpackage delegates
 */
class Launcher implements LauncherInterface
{
    /**
     * @var string
     */
    private static $nice_options = 'nice -n 19';

    /**
     * @var string
     */
    private static $ionice_options = 'ionice -c 2 -n 7';

    /**
     * @var bool
     */
    private $be_nice = false;

    /**
     * Return true if $pid process is running.
     *
     * @param  int  $pid
     * @return bool
     */
    public function isRunning($pid)
    {
        return count(preg_split("/\n/", shell_exec(sprintf('ps %d', $pid)))) > 2;
    }

    /**
     * Launch command and return its result.
     *
     * @param         $command_name
     * @param  array  $arguments
     * @param  array  $options
     * @param  string $run_from
     * @param  bool   $be_nice
     * @return string
     */
    public function launchCommand($command_name, $arguments = null, $options = null, $run_from = null, $be_nice = false)
    {
        $this->be_nice = (bool) $be_nice;

        return $this->launchProcess($this->prepareCommand($command_name, $arguments, $options), $run_from);
    }

    /**
     * Execute process and return its result.
     *
     * @param  string $command
     * @param  string $run_from
     * @return string
     */
    public function launchProcess($command, $run_from = null)
    {
        if (!is_null($run_from)) {
            $this->chdir($run_from);
        }

        return $this->be_nice ? self::shell_exec_nice($command) : shell_exec($command);
    }

    /**
     * Change current working directory.
     *
     * @param $dir
     * @throws FileDnxError
     */
    private function chdir($dir)
    {
        if (!is_dir($dir)) {
            // @TODO: Need to make DirectoryDnxError :)
            throw new FileDnxError($dir);
        }

        chdir($dir);
    }

    /**
     * Run any command with nice+ionice.
     *
     * @param $command
     * @return string
     */
    public static function shell_exec_nice($command)
    {
        $options = self::getAllNiceOptions();

        $options[] = $command;

        $cmd = implode(' ', $options);

        return shell_exec($cmd);
    }

    /**
     * Get nice options.
     *
     * @param  bool         $as_string
     * @return array|string
     */
    public static function getAllNiceOptions($as_string = false)
    {
        $options = [];

        $options[] = self::$nice_options;

        if (PHP_OS !== 'Darwin') { // macs don't have ionice
            $options[] = self::$ionice_options;
        }

        return $as_string ? implode(' ', $options) : $options;
    }

    /**
     * Prepare command based on input parameters.
     *
     * @param        $command_name
     * @param  array $arguments
     * @param  array $options
     * @return int
     */
    private function prepareCommand($command_name, $arguments = null, $options = null)
    {
        $command_elements = [$command_name];

        if (is_foreachable($arguments)) {
            foreach ($arguments as $argument) {
                $command_elements[] = escapeshellarg($argument);
            }
        }

        if (is_foreachable($options)) {
            foreach ($options as $option_name => $option_value) {
                if (!$this->isValidOptionName($option_name)) {
                    throw new InvalidParamError('options', $options, "'$option_name' is not valid option name");
                }

                if (strlen($option_name) == 1) {
                    if ($option_value === true) {
                        $command_elements[] = "-{$option_name}";
                    } else {
                        $command_elements[] = "-{$option_name} " . escapeshellarg($option_value);
                    }
                } else {
                    if ($option_value === true) {
                        $command_elements[] = "--{$option_name}";
                    } else {
                        $command_elements[] = "--{$option_name}=" . escapeshellarg($option_value);
                    }
                }
            }
        }

        return implode(' ', $command_elements);
    }

    /**
     * Returns true if $option_name is valid option name.
     *
     * @param  string $option_name
     * @return bool
     */
    private function isValidOptionName($option_name)
    {
        return preg_match('/\W/', str_replace('-', '_', $option_name)) == 0;
    }

    /**
     * Launch command in the background and return process ID.
     *
     * @param         $command_name
     * @param  array  $arguments
     * @param  array  $options
     * @param  string $run_from
     * @param  bool   $be_nice
     * @return int
     */
    public function launchBackgroundCommand($command_name, $arguments = null, $options = null, $run_from = null, $be_nice = false)
    {
        $this->be_nice = (bool) $be_nice;

        return $this->launchBackgroundProcess($this->prepareCommand($command_name, $arguments, $options), $run_from);
    }

    /**
     * Launch process in the background and return file name where process ID will be stored.
     *
     * @param  string              $command
     * @param  string              $output_file
     * @param  string              $run_from
     * @return int
     * @throws FileDnxError
     * @throws NotImplementedError
     */
    public function launchBackgroundProcess($command, $output_file = null, $run_from = null)
    {
        if (empty($output_file)) {
            $output_file = AngieApplication::getAvailableWorkFileName('command_log');
        }

        if (!is_null($run_from)) {
            $this->chdir($run_from);
        }

        if (DIRECTORY_SEPARATOR == '\\') {
            throw new NotImplementedError(__METHOD__, 'Background process starting is not implemented for Windows platform');
//        $WshShell = new COM("WScript.Shell");
//        $oExec = $WshShell->Run($command, 0, false);
        } else {
            $prepared_command = "$command > $output_file 2>&1 & echo $!";

            return (int) $this->be_nice ? $this->shell_exec_nice($prepared_command) : shell_exec($prepared_command);
        }
    }

    /**
     * Get prepared command(s) to forward it to something else.
     *
     * @param  string      $command_name
     * @param  array|null  $arguments
     * @param  array|null  $options
     * @param  string|null $run_from
     * @param  bool        $be_nice
     * @return string
     */
    public function getFullCommandString($command_name, $arguments = null, $options = null, $run_from = null, $be_nice = false)
    {
        $this->be_nice = (bool) $be_nice;
        $commands = [];
        $old_cwd = null;

        if (!is_null($run_from)) {
            $old_cwd = getcwd();
            $commands[] = 'cd ' . escapeshellarg($run_from);
        }

        $commands[] = $this->prepareCommand($command_name, $arguments, $options);

        if (!is_null($run_from) && !is_null($old_cwd)) {
            $commands[] = 'cd ' . escapeshellarg($old_cwd);
        }

        return count($commands) > 1 ? implode('; ', $commands) : $commands['0'];
    }
}
