<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddBruteForceProtectionSettings extends AngieModelMigration
{
    public function up()
    {
        $firewall_settings = $this->getConfigOptionValue('firewall_settings');

        if (is_array($firewall_settings) && !empty($firewall_settings)) {
            $brute_force_cooldown_lenght = $this->getIntSetting($firewall_settings, 'min_block_time', 600);
            $brute_force_cooldown_threshold = $this->getIntSetting($firewall_settings, 'max_attempts', 5);

            if (array_key_exists('alert_admin_on', $firewall_settings)) {
                unset($firewall_settings['alert_admin_on']);
            }

            $this->setConfigOptionValue('firewall_settings', $firewall_settings);
        } else {
            $brute_force_cooldown_lenght = 300;
            $brute_force_cooldown_threshold = 5;
        }

        $this->addConfigOption('brute_force_protection_enabled', true);
        $this->addConfigOption('brute_force_cooldown_lenght', $brute_force_cooldown_lenght);
        $this->addConfigOption('brute_force_cooldown_threshold', $brute_force_cooldown_threshold);
    }

    /**
     * Return firewall setting by the given name, and unset it from the settings array.
     *
     * @param  string $setting_name
     * @param  int    $default_value
     * @return int
     */
    private function getIntSetting(array &$firewall_settings, $setting_name, $default_value)
    {
        $value = $default_value;

        if (array_key_exists($setting_name, $firewall_settings)) {
            $value = (int) $firewall_settings[$setting_name];
            unset($firewall_settings[$setting_name]);
        }

        if ($value < 1) {
            $value = $default_value;
        }

        return $value;
    }
}
