<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

final class AngieApplicationEnvironmentChecker
{
    /**
     * @var string
     */
    private $min_php_version = '8.0.0';

    /**
     * @var string
     */
    protected $min_mysql_version = '5.7.8';

    /**
     * Min MariaDB version.
     *
     * @var string
     */
    protected $min_mariadb_version = '10.2.7';

    /**
     * @var array
     */
    protected $required_php_extensions = [
        'mysqli',
        'pcre',
        'tokenizer',
        'ctype',
        'session',
        'json',
        'xml',
        'dom',
        'phar',
        'openssl',
        'gd',
        'mbstring',
        'curl',
        'zlib',
        'fileinfo',
        'hash',
    ];

    /**
     * @var array
     */
    private $log = [];

    /**
     * @var bool
     */
    private $all_ok = true;

    /**
     * @var callable
     */
    private $on_pass;

    /**
     * @var callable
     */
    private $on_fail;

    /**
     * @return bool
     */
    public function check(callable $on_pass = null, callable $on_fail = null)
    {
        $this->all_ok = true;
        $this->on_pass = $on_pass;
        $this->on_fail = $on_fail;

        if (version_compare(PHP_VERSION, $this->min_php_version, '>=')) {
            $this->pass('Your PHP is ' . PHP_VERSION);
        } else {
            $this->fail('PHP version that is required to run the system is ' . $this->min_php_version . '. You have ' . PHP_VERSION);
        }

        foreach ($this->required_php_extensions as $required_php_extension) {
            if (extension_loaded($required_php_extension)) {
                $this->pass('"' . $required_php_extension . '" extension is available');
            } else {
                $this->fail('Required "' . $required_php_extension . '" PHP extension was not found. Please install it before continuing');
            }
        }

        $memory_limit = $this->getMemoryLimit();

        if ($memory_limit == -1 || $memory_limit >= 67108864) {
            $formatted_memory_limit = $memory_limit == -1 ? 'unlimited' : format_file_size($memory_limit);
            $this->pass('Your memory limit is ' . $formatted_memory_limit);
        } else {
            $this->fail('Your memory is too low to complete the upgrade. Minimal value is 64MB, and you have it set to ' . format_file_size($memory_limit));
        }

        if (extension_loaded('mysqli')) {
            $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($link instanceof mysqli) {
                $mysql_version = $this->getMySqlVersion($link);

                [$mysql_server, $min_mysql_server_version, $mysql_version_ok] = $this->validateMySqlVersion(
                    $mysql_version,
                    $this->min_mysql_version,
                    $this->min_mariadb_version,
                );

                if ($mysql_version_ok) {
                    $this->pass("Your {$mysql_server} version is {$mysql_version}");
                } else {
                    $this->fail("{$mysql_server} version that is required to run the system is {$min_mysql_server_version}. You have {$mysql_version}");
                }

                if ($this->checkHaveInnoDb($link)) {
                    $this->pass("Your {$mysql_server} has InnoDB storage engine support");
                } else {
                    $this->fail("Your {$mysql_server} does not have InnoDB storage engine support");
                }

                if ($this->checkHaveUtf8mb4($link)) {
                    $this->pass("Your {$mysql_server} has UTF8MB4 character set support");
                } else {
                    $this->fail("Your {$mysql_server} does not have UTF8MB4 character set support");
                }

                if ($this->checkThreadStack($link)) {
                    $this->pass("{$mysql_server} thread stack is 256kb");
                } else {
                    $this->fail("{$mysql_server} thread stack should be 256kb");
                }
            } else {
                $this->fail('Failed to connect to database');
            }
        } else {
            $this->fail('MySQLi extension is required to connect to database');
        }

        if (folder_is_writable(ROOT)) {
            $this->pass('/' . basename(ROOT) . ' directory is writable');
        } else {
            $this->fail('/' . basename(ROOT) . ' directory is not writable. Make it writable to continue');
        }

        if (folder_is_writable(ASSETS_PATH)) {
            $this->pass('/public/assets is writable');
        } else {
            $this->fail('/public/assets is not writable. Make it writable to continue');
        }

        if (file_is_writable(CONFIG_PATH . '/version.php')) {
            $this->pass('/config/version.php is writable');
        } else {
            $this->fail('/config/version.php is not writable. Make it writable to continue');
        }

        return $this->all_ok;
    }

    private function getMemoryLimit()
    {
        $val = trim(ini_get('memory_limit'));
        $last = strtolower($val[strlen($val) - 1]);

        if (!ctype_digit($last)) {
            $val = substr($val, 0, strlen($val) - 1);
        }

        if ($last === 'g') {
            $val *= 1024 * 1024 * 1024;
        } elseif ($last === 'm') {
            $val *= 1024 * 1024;
        } elseif ($last === 'k') {
            $val *= 1024;
        }

        return (int) floor((float) $val);
    }

    private function getMySqlVersion(mysqli $link)
    {
        if ($result = $link->query("SELECT VERSION() AS 'version'")) {
            while ($row = $result->fetch_assoc()) {
                return $row['version'];
            }
        }

        return $link->get_server_info();
    }

    private function validateMySqlVersion($version, $min_mysql_version, $min_mariadb_version)
    {
        if (strpos(strtolower($version), 'mariadb') !== false) {
            return [
                'MariaDB',
                $min_mariadb_version,
                strpos($version, $min_mariadb_version) !== false
                    || version_compare($version, $min_mariadb_version) >= 0,
            ];
        } else {
            return [
                'MySQL',
                $min_mysql_version,
                version_compare($version, $min_mysql_version) >= 0,
            ];
        }
    }

    /**
     * Return true if MySQL supports InnoDB storage engine.
     *
     * @return bool
     */
    private function checkHaveInnoDb(mysqli $link)
    {
        if ($result = $link->query('SHOW ENGINES')) {
            while ($engine = $result->fetch_assoc()) {
                if (strtolower($engine['Engine']) == 'innodb' && in_array(strtolower($engine['Support']), ['yes', 'default'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  mysqli $link
     * @return bool
     */
    private function checkHaveUtf8mb4($link)
    {
        if ($result = $link->query("SHOW CHARACTER SET LIKE 'utf8mb4'")) {
            while ($charset = $result->fetch_assoc()) {
                if (strtolower($charset['Charset']) == 'utf8mb4') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  mysqli $link
     * @return bool
     */
    private function checkThreadStack($link)
    {
        if ($result = $link->query("SELECT @@thread_stack AS 'thread_stack'")) {
            while ($row = $result->fetch_assoc()) {
                return (int) $row['thread_stack'] >= 262144; // 256kb
            }
        }

        return false;
    }

    /**
     * @param string $message
     */
    public function pass($message)
    {
        if ($this->on_pass && is_callable($this->on_pass)) {
            call_user_func($this->on_pass, $message);
        }
    }

    /**
     * @param string $message
     */
    public function fail($message)
    {
        $this->all_ok = false;

        if ($this->on_fail && is_callable($this->on_fail)) {
            call_user_func($this->on_fail, $message);
        }
    }
}
