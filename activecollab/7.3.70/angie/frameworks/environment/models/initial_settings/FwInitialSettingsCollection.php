<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\System\Wires\AvatarProxy;
use Angie\Events;
use Angie\FeatureFlags\FeatureFlagsInterface;
use Angie\Globalization;

/**
 * Initial settings and collections collection.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
abstract class FwInitialSettingsCollection extends AbstractInitialSettingsCollection
{
    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $bits = [
                ConfigOptions::getValue('initial_settings_timestamp', $use_cache),
                $this->getDeploymentChannel(),
                implode(',', $this->getFeatureFlags()),
            ];

            if (!AngieApplication::isOnDemand()) {
                $bits[] = $this->getConfigFileTimestamp();
            }

            $this->tag = $this->prepareTagFromBits(
                $user->getEmail(),
                sha1(implode('-', $bits))
            );
        }

        return $this->tag;
    }

    /**
     * Run the query and return DB result.
     *
     * @return DbResult|DataObject[]
     */
    public function execute()
    {
        return array_merge(
            parent::execute(),
            [
                'timestamp' => ConfigOptions::getValue('initial_settings_timestamp'),
            ]
        );
    }

    /**
     * @var array
     */
    private $settings = false;

    /**
     * @return array
     */
    protected function getSettings()
    {
        if ($this->settings === false) {
            $this->settings = [
                'identity_name' => ConfigOptions::getValue('identity_name'),
                'on_demand' => AngieApplication::isOnDemand(),
                'is_angie_in_test' => AngieApplication::isInTestMode(),
                'in_development' => AngieApplication::isInDevelopment(),
                'deployment_channel' => $this->getDeploymentChannel(),
                'built_in_language' => Languages::getBuiltIn(),
                'default_currency_id' => Currencies::getDefaultId(),
                'default_labels_name_max_length' => Labels::LABELS_NAME_MAX_LENGTH,
                'gd_loaded' => extension_loaded('gd'),
                'avatar_url' => AngieApplication::getProxyUrl(
                    AvatarProxy::class,
                    SystemModule::NAME,
                    [
                        'user_id' => '--USER-ID--',
                        'user_name' => '--USER-NAME--',
                        'user_email' => '--USER-EMAIL--',
                        'size' => '--SIZE--',
                        'timestamp' => '--UPDATED-ON--',
                    ]
                ),
                'show_visual_editor_toolbar' => ConfigOptions::getValue('show_visual_editor_toolbar'),
                'timezone' => ConfigOptions::getValue('time_timezone'),
                'gmt_offset' => Globalization::getGmtOffset(),
                'feature_flags' => $this->getFeatureFlags(),
            ];

            $this->onLoadSettings($this->settings, $this->getWhosAsking());

            Events::trigger(
                'on_initial_settings',
                [
                    &$this->settings,
                    $this->getWhosAsking(),
                ]
            );
        }

        return $this->settings;
    }

    /**
     * @var array
     */
    private $collections = false;

    /**
     * @return ModelCollection[]
     */
    protected function &getCollections()
    {
        if ($this->collections === false) {
            $this->collections = [
                'currencies' => Currencies::prepareCollection(DataManager::ALL, $this->getWhosAsking()),
                'languages' => Languages::prepareCollection(DataManager::ALL, $this->getWhosAsking()),
            ];

            $this->onLoadCollections($this->collections, $this->getWhosAsking());

            Events::trigger(
                'on_initial_collections',
                [
                    &$this->collections,
                    $this->getWhosAsking(),
                ]
            );
        }

        return $this->collections;
    }

    private function getConfigFileTimestamp(): int
    {
        if (is_file(CONFIG_PATH . '/config.php')) {
            $config_file_modification_time = (int) filemtime(CONFIG_PATH . '/config.php');

            if ($config_file_modification_time) {
                return (int) $config_file_modification_time;
            }
        }

        return 0;
    }

    private function getFeatureFlags(): array
    {
        return $this->getFeatureFlagsResolver()->getFeatureFlags();
    }

    private $deployment_channel;

    private function getDeploymentChannel(): string
    {
        return $this->deployment_channel ?? AngieApplication::getDeploymentChannel();
    }

    public function setDeploymentChannel(?string $deployment_channel)
    {
        $this->deployment_channel = $deployment_channel;
    }

    private ?FeatureFlagsInterface $feature_flags_resolver = null;

    private function getFeatureFlagsResolver(): FeatureFlagsInterface
    {
        return $this->feature_flags_resolver ?? AngieApplication::getContainer()->get(FeatureFlagsInterface::class);
    }

    public function setFeatureFlagsResolver(?FeatureFlagsInterface $feature_flags_resolver)
    {
        $this->feature_flags_resolver = $feature_flags_resolver;
    }
}
