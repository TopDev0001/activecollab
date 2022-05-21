<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\App\Channel\OnDemandChannelInterface;
use ActiveCollab\Module\System\Events\Maintenance\DailyMaintenanceEvent;
use Angie\Events;
use Angie\Globalization;

class CronIntegration extends Integration
{
    const HOURLY_MAINTENANCE = 'hourly_maintenance';
    const DAILY_MAINTENANCE = 'daily_maintenance';
    const MORNING_MAIL = 'morning_mail';
    const ACCOUNT_STATUS = 'account_status';

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Cron Jobs';
    }

    public function getShortName(): string
    {
        return 'cron';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Use Cron jobs to perform various system tasks');
    }

    /**
     * Return true if this integration is available for on-demand packages.
     *
     * @return bool
     */
    public function isAvailableForOnDemand()
    {
        return false; // Use pre-configured mailing and don't let settings be changed via API
    }

    public function jsonSerialize(): array
    {
        $php_executable_path = $this->getExecutableFromPaths();

        $frequently_command = $php_executable_path . ' ' . escapeshellarg(ENVIRONMENT_PATH . '/tasks/cron_jobs/run_every_minute.php');
        $hourly_command = $php_executable_path . ' ' . escapeshellarg(ENVIRONMENT_PATH . '/tasks/cron_jobs/run_every_hour.php');
        $check_imap_command = $php_executable_path . ' ' . escapeshellarg(ENVIRONMENT_PATH . '/tasks/cron_jobs/check_imap_every_3_minutes.php');

        if (DIRECTORY_SEPARATOR == '\\') {
            $platform = 'windows';

            $setup_note = 'IIS';
            $setup_frequently = 'schtasks /create /ru "IIS" /sc minute /tn "ActiveCollab Frequently Job" /tr "' . $frequently_command . '"';
            $setup_hourly = 'schtasks /create /ru "IIS" /sc hourly /st 12:00:00 /tn "ActiveCollab Hourly Job" /tr "' . $hourly_command . '"';
            $setup_check_imap = 'schtasks /create /ru "IIS" /sc minute /mo 3 /tn "ActiveCollab Inbound Email Job" /tr "' . $check_imap_command . '"';
        } else {
            $platform = 'unix';

            $setup_note = '';
            $setup_frequently = '(crontab -l ; echo "* * * * * ' . $frequently_command . '") | sort - | uniq - | crontab -';
            $setup_hourly = '(crontab -l ; echo "0 * * * * ' . $hourly_command . '") | sort - | uniq - | crontab -';
            $setup_check_imap = '(crontab -l ; echo "*/3 * * * * ' . $check_imap_command . '") | sort - | uniq - | crontab -';
        }

        return array_merge(
            parent::jsonSerialize(),
            [
                'maintenance_at' => 4,
                'morning_mail_at' => 7,
                'account_status_at' => 8,
                'instructions' => [
                    'platform' => $platform,
                    'frequently' => $frequently_command,
                    'hourly' => $hourly_command,
                    'imap_check' => $check_imap_command,
                    'setup_note' => $setup_note,
                    'setup_frequently' => $setup_frequently,
                    'setup_hourly' => $setup_hourly,
                    'setup_check_imap' => $setup_check_imap,
                    'frequently_last_run' => AngieApplication::memories()->get('frequently_last_run', null, false),
                    'hourly_last_run' => AngieApplication::memories()->get('hourly_last_run', null, false),
                    'check_imap_last_run' => AngieApplication::memories()->get('check_imap_last_run', null, false),
                ],
            ]
        );
    }

    /**
     * Return PATH environment variable and try to get PHP binary path from.
     *
     * @return string
     */
    public function getExecutableFromPaths()
    {
        if (DISCOVER_PHP_CLI) {
            $exacutable_names = ['php', 'php-cli', 'php56', 'php55', 'php54'];

            foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $path) {
                foreach ($exacutable_names as $exacutable_name) {
                    if (strstr($path, "{$exacutable_name}.exe") && isset($_SERVER['WINDIR']) && file_exists($path) && is_file($path) && $this->checkPhpVersion($path)) {
                        return $path;
                    } else {
                        $php_executable = $path . DIRECTORY_SEPARATOR . $exacutable_name;

                        if (isset($_SERVER['WINDIR'])) {
                            $php_executable .= '.exe';
                        }

                        if (file_exists($php_executable) && is_file($php_executable) && $this->checkPhpVersion($php_executable)) {
                            return $php_executable;
                        }
                    }
                }
            }
        }

        return DIRECTORY_SEPARATOR == '\\' ? 'C:\\path\\to\\php.exe' : '/path/to/your/php';
    }

    /**
     * Try to run php -v for the given binary and check the output.
     *
     * @param  string $executable_path
     * @return bool
     */
    private function checkPhpVersion($executable_path)
    {
        $output = [];
        $exit_code = 0;

        exec("$executable_path -v", $output, $exit_code);

        if ($exit_code === 0 && count($output)) {
            $bits = explode(' ', $output[0]);

            return count($bits) >= 2 && $bits[0] == 'PHP' && version_compare($bits[1], '5.4') >= 0;
        }

        return false;
    }

    /**
     * Returns true if cron jobs are configured properly and are being fired.
     *
     * @return bool
     */
    public function isOk(array & $error_messages = null)
    {
        $current_timestamp = time();

        if ((int) AngieApplication::memories()->get('frequently_last_run', null, false) < $current_timestamp - 300) {
            if ($error_messages === null) {
                return false;
            } else {
                $error_messages[] = lang('Frequently Cron job did not run in the past 5 minutes');
            }
        }

        if ((int) AngieApplication::memories()->get('check_imap_last_run', null, false) < $current_timestamp - 300) {
            if ($error_messages === null) {
                return false;
            } else {
                $error_messages[] = lang('Inbound email Cron job did not run in the past 5 minutes');
            }
        }

        if ((int) AngieApplication::memories()->get('hourly_last_run', null, false) < $current_timestamp - 3900) {
            if ($error_messages === null) {
                return false;
            } else {
                $error_messages[] = lang('Hourly Cron job did not run in the past hour');
            }
        }

        return empty($error_messages);
    }

    // ---------------------------------------------------
    //  Maintenance, Morining Mail and Account Status
    // ---------------------------------------------------

    /**
     * Run every hour.
     *
     * @param int $timestamp
     */
    public function runEveryHour($timestamp, callable $output)
    {
        if (DB::executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM `memories` WHERE `key` = 'hourly_last_run'")) {
            DB::execute("UPDATE `memories` SET `value` = ?, `updated_on` = ? WHERE `key` = 'hourly_last_run'", serialize($timestamp), date('Y-m-d H:i:s'));
        } else {
            DB::execute("INSERT INTO `memories` (`key`, `value`, `updated_on`) VALUES ('hourly_last_run', ?, ?)", serialize($timestamp), date('Y-m-d H:i:s'));
        }

        switch ($this->whichEventShouldBeDoneAt($timestamp)) {
            case self::DAILY_MAINTENANCE:
                call_user_func($output, 'Performing daily maintenance');
                $this->triggerHourlyEvent(self::DAILY_MAINTENANCE, $timestamp);
                break;
            case self::MORNING_MAIL:
                call_user_func($output, 'Sending morning mail');
                $this->triggerHourlyEvent(self::MORNING_MAIL, $timestamp);
                break;
            case self::ACCOUNT_STATUS:
                call_user_func($output, 'Sending morning mail');
                $this->triggerHourlyEvent(self::ACCOUNT_STATUS, $timestamp);
                break;
        }

        call_user_func($output, 'Performing hourly maintenance');
        $this->triggerHourlyEvent(self::HOURLY_MAINTENANCE, $timestamp);
    }

    /**
     * Return which event shiuld be performed at the given timestamp.
     *
     * @param  int    $timestamp
     * @return string
     */
    public function whichEventShouldBeDoneAt($timestamp)
    {
        if ($this->shouldUpdateAccountStatus($timestamp)) {
            return self::ACCOUNT_STATUS;
        } elseif ($this->shouldSendMorningMail($timestamp)) {
            return self::MORNING_MAIL;
        } elseif ($this->shouldDoDailyMaintenance($timestamp)) {
            return self::DAILY_MAINTENANCE;
        } else {
            return self::HOURLY_MAINTENANCE;
        }
    }

    /**
     * Return true if maintenance should be performed for the given timestamp.
     *
     * @param  int  $timestamp
     * @return bool
     */
    public function shouldDoDailyMaintenance($timestamp)
    {
        return $this->shouldDoEvent(self::DAILY_MAINTENANCE, 4, $timestamp);
    }

    /**
     * Return true if maintenance should be performed for the given timestamp.
     *
     * @param  string $event
     * @param  int    $it_should_be_done_at
     * @param  int    $timestamp
     * @return bool
     */
    private function shouldDoEvent($event, $it_should_be_done_at, $timestamp)
    {
        if ($this->isEventDone($event, $timestamp)) {
            return false;
        }

        $hour = date('H', $this->getLocalTimestamp($timestamp));

        return $hour >= $it_should_be_done_at && $hour < 12;
    }

    // ---------------------------------------------------
    //  Event Logs Utils
    // ---------------------------------------------------

    /**
     * Return true if morning mail should be sent for the given timestamp.
     *
     * @param  int  $timestamp
     * @return bool
     */
    public function shouldSendMorningMail($timestamp)
    {
        return $this->shouldDoEvent(self::MORNING_MAIL, 7, $timestamp);
    }

    /**
     * Return true if account status should be updated for the given timestamp.
     *
     * @param  int  $timestamp
     * @return bool
     */
    public function shouldUpdateAccountStatus($timestamp)
    {
        return $this->shouldDoEvent(self::ACCOUNT_STATUS, 8, $timestamp);
    }

    // ---------------------------------------------------
    //  Maintenance
    // ---------------------------------------------------

    /**
     * Perform daily maintenance tasks.
     */
    public function dailyMaintenance(int $timestamp = null)
    {
        if (empty($timestamp)) {
            $timestamp = AngieApplication::currentTimestamp()->getCurrentTimestamp();
        }

        $this->triggerHourlyEvent(self::DAILY_MAINTENANCE, $timestamp);
    }

    private function triggerHourlyEvent(string $event_name, int $timestamp)
    {
        $day = DateTimeValue::now()->getSystemDate();

        switch ($event_name) {
            case self::DAILY_MAINTENANCE:
                $this->setDailyMaintenanceDone($timestamp, 'Trying to perform maintenance');

                AngieApplication::eventsDispatcher()->trigger(new DailyMaintenanceEvent($day));
                Events::trigger('on_daily_maintenance');

                $this->setDailyMaintenanceDone($timestamp);

                $notification_subject = 'Daily Maintenance Performed';
                $notification_body = 'Daily maintenance for %s has been successfully performed. Time taken: %s seconds.';

                break;
            case self::MORNING_MAIL:
                $this->setMorningMailDone($timestamp, 'Trying to send morning mail');
                Events::trigger('on_morning_mail');
                $this->setMorningMailDone($timestamp);

                $notification_subject = 'Morning Mail Sent';
                $notification_body = 'Morning mail for %s has successfuly been sent. Time taken: %s seconds.';

                break;
            case self::ACCOUNT_STATUS:
                $this->setAccountStatusDone($timestamp, 'Trying to update account status');
                Events::trigger('on_account_status');
                $this->setAccountStatusDone($timestamp);

                $notification_subject = 'Account Status Updated';
                $notification_body = 'Account status for %s has successfuly been updated. Time taken: %s seconds.';

                break;
            default:
                Events::trigger('on_hourly_maintenance');

                $notification_subject = 'Hourly Maintenance Performed';
                $notification_body = 'Hourly maintenance for %s has been successfully performed. Time taken: %s seconds.';
        }

        $time_to_perform = round(microtime(true) - ANGIE_SCRIPT_TIME, 5);

        if (AngieApplication::getContainer()->get(OnDemandChannelInterface::class)->isEdgeChannel() && AngieApplication::getAccountId() === 1) {
            /** @var InfoNotification $info_notification */
            $info_notification = AngieApplication::notifications()
                ->notifyAbout(EnvironmentFramework::INJECT_INTO . '/info');
            $info_notification->setCustomSubject($notification_subject);
            $info_notification->setCustomMessage(
                sprintf(
                    $notification_body,
                    ROOT_URL,
                    (string) $time_to_perform
                )
            );
            $info_notification->sendToUsers(DataObjectPool::get(Owner::class, 1), true);
        }
    }

    /**
     * Perform hourly maintenance.
     */
    public function hourlyMaintenance()
    {
        Events::trigger('on_hourly_maintenance');
    }

    /**
     * Set a daily maintenance as done for a given day.
     *
     * @param int         $timestamp
     * @param string|null $error_message
     */
    public function setDailyMaintenanceDone($timestamp, $error_message = null)
    {
        $this->setEventDone(self::DAILY_MAINTENANCE, $timestamp, $error_message);
    }

    /**
     * Return true if maintenance has already been performed for the given date.
     *
     * @param  int  $timestamp
     * @return bool
     */
    public function isDailyMaintenanceDone($timestamp)
    {
        return $this->isEventDone(self::DAILY_MAINTENANCE, $timestamp);
    }

    /**
     * Return true if morning has already been sent for the given date.
     *
     * @param  int  $timestamp
     * @return bool
     */
    public function isMorningMailSent($timestamp)
    {
        return $this->isEventDone(self::MORNING_MAIL, $timestamp);
    }

    /**
     * Set morning mail as done for a given day.
     *
     * @param int         $timestamp
     * @param string|null $error_message
     */
    public function setMorningMailDone($timestamp, $error_message = null)
    {
        $this->setEventDone(self::MORNING_MAIL, $timestamp, $error_message);
    }

    /**
     * Return true if account status has already been update for the given date.
     *
     * @param  int  $timestamp
     * @return bool
     */
    public function isAccountStatusUpdated($timestamp)
    {
        return $this->isEventDone(self::ACCOUNT_STATUS, $timestamp);
    }

    /**
     * Set account status as done for a given day.
     *
     * @param int         $timestamp
     * @param string|null $error_message
     */
    public function setAccountStatusDone($timestamp, $error_message = null)
    {
        $this->setEventDone(self::ACCOUNT_STATUS, $timestamp, $error_message);
    }

    /**
     * Return true if maintenance has already been performed for the given date.
     *
     * @param  string $event
     * @param  int    $timestamp
     * @return bool
     */
    private function isEventDone($event, $timestamp)
    {
        $log = AngieApplication::memories()->get("{$event}_log");

        return $log && isset($log[date('Y-m-d', $this->getLocalTimestamp($timestamp))]);
    }

    /**
     * Set a maintenance or morning mail as done for a given day.
     *
     * @param string      $event
     * @param int         $timestamp
     * @param string|null $error_message
     */
    private function setEventDone($event, $timestamp, $error_message = null)
    {
        $log = AngieApplication::memories()->get("{$event}_log");

        if (empty($log)) {
            $log = [];
        }

        $log[date('Y-m-d', $this->getLocalTimestamp($timestamp))] = $error_message
            ? ['ok' => false, 'error_message' => $error_message]
            : ['ok' => true];

        AngieApplication::memories()->set("{$event}_log", $log);
    }

    /**
     * Correct timestamp with system's GMT offset.
     */
    private function getLocalTimestamp(int $timestamp): int
    {
        return $timestamp + Globalization::getGmtOffset();
    }
}
