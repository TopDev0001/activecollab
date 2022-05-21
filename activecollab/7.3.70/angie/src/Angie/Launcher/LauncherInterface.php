<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Launcher;

use FileDnxError;
use NotImplementedError;

/**
 * Angie process launched delegate implementation.
 *
 * @package angie.library.application
 * @subpackage delegates
 */
interface LauncherInterface
{
    /**
     * Return true if $pid process is running.
     *
     * @param  int  $pid
     * @return bool
     */
    public function isRunning($pid);

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
    public function launchCommand($command_name, $arguments = null, $options = null, $run_from = null, $be_nice = false);

    /**
     * Execute process and return its result.
     *
     * @param  string $command
     * @param  string $run_from
     * @return string
     */
    public function launchProcess($command, $run_from = null);

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
    public function launchBackgroundCommand($command_name, $arguments = null, $options = null, $run_from = null, $be_nice = false);

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
    public function launchBackgroundProcess($command, $output_file = null, $run_from = null);

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
    public function getFullCommandString($command_name, $arguments = null, $options = null, $run_from = null, $be_nice = false);
}
