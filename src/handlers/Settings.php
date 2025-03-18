<?php

namespace ColinDorr\CraftcmsBetterUpdates\handlers;

use Craft;
use craft\helpers\App;
use craft\web\Application;
use Symfony\Component\Yaml\Yaml;

use ColinDorr\CraftcmsBetterUpdates\Plugin;
use ColinDorr\CraftcmsBetterUpdates\handlers\Updates;
use ColinDorr\CraftcmsBetterUpdates\handlers\Validations;

class Settings
{
    /**
     * Retrieve all plugin settings.
     */
    public static function getSettings(): array
    {
        $pluginSettings = Craft::$app->plugins->getPlugin(Plugin::$plugin_handle)->settings;
        return [
            "email" => self::getSettingsEmail($pluginSettings),
            "version_type" => self::getSettingsVersionType($pluginSettings),
            "day_of_week" => self::getSettingsDayOfWeek($pluginSettings),
            "frequency" => self::getSettingsFrequency($pluginSettings),
            "next_planned_email_timestamp" => self::getSettingsNextPlannedEmailTimestamp($pluginSettings),
        ];
    }

    /**
     * Get the path to the control panel icon.
     */
    public static function getIconCpPath(): ?string
    {
        return Craft::getAlias("@" . Plugin::$plugin_handle . '/resources/assets/icon-cp.svg');
    }

    /**
     * Get the path to the plugin icon.
     */
    public static function getIconPath(): ?string
    {
        return Craft::getAlias("@" . Plugin::$plugin_handle . '/resources/assets/icon.svg');
    }

    /**
     * Get the site name from Craft CMS.
     */
    public static function getSiteName(): string 
    {
        return Craft::$app->getSites()->getPrimarySite()->name;
    }

    /**
     * Retrieve the system email from project.yaml.
     */
    public static function getSystemEmail()
    {
        $projectYamlFilePath = Craft::getAlias('@config/project/project.yaml');

        if (file_exists($projectYamlFilePath)) {
            $parsedData = Yaml::parseFile($projectYamlFilePath);
            return $parsedData['email']['fromEmail'] ?? null;
        }

        return null;
    }

    /**
     * Craft's license key from either the environment or license file
     */
    public static function getCraftLicenseKey(): ?string
    {
        return App::env('CRAFT_LICENSE_KEY') ?: (file_exists(Craft::getAlias('@config/license.key')) ? file_get_contents(Craft::getAlias('@config/license.key')) : null);
    }

    /**
     * Get the email setting from plugin configuration.
     */
    public static function getSettingsEmail($pluginSettings = null): string
    {
        $pluginSettings = $pluginSettings ?? Craft::$app->plugins->getPlugin(Plugin::$plugin_handle)->settings;
        return $pluginSettings->email ?? '';
    }

    /**
     * Get the version type setting.
     */
    public static function getSettingsVersionType($pluginSettings = null): string
    {
        $pluginSettings = $pluginSettings ?? Craft::$app->plugins->getPlugin(Plugin::$plugin_handle)->settings;
        return $pluginSettings->version_type ?? 'Minor';
    }

    /**
     * Get the preferred day of the week for notifications.
     */
    public static function getSettingsDayOfWeek($pluginSettings = null): string
    {
        $pluginSettings = $pluginSettings ?? Craft::$app->plugins->getPlugin(Plugin::$plugin_handle)->settings;
        return $pluginSettings->day_of_week ?? '';
    }

    /**
     * Get the frequency setting for update checks.
     */
    public static function getSettingsFrequency($pluginSettings = null): string
    {
        $pluginSettings = $pluginSettings ?? Craft::$app->plugins->getPlugin(Plugin::$plugin_handle)->settings;
        return $pluginSettings->frequency ?? '';
    }

    /**
     * Get the next planned email notification timestamp.
     */
    public static function getSettingsNextPlannedEmailTimestamp($pluginSettings = null): string
    {
        $pluginSettings = $pluginSettings ?? Craft::$app->plugins->getPlugin(Plugin::$plugin_handle)->settings;
        return $pluginSettings->next_planned_email_timestamp ?? 0;
    }

    /**
     * Set the next planned email timestamp based on frequency.
     */
    public static function setSettingsNextPlannedEmailTimestamp(): void
    {
        $dates = Validations::getGetDates();
        $frequency = self::getSettingsFrequency();
        $selected_frequency = $dates["selected_frequency"];

        self::setSettingsValue("next_planned_email_timestamp", $selected_frequency);
    }

    /**
     * Update a specific setting in the plugin configuration.
     */
    public static function setSettingsValue($key = null, $value = null): void
    {
        if ($key === null || $value === null) {
            return;
        }

        $plugin = Craft::$app->plugins->getPlugin(Plugin::$plugin_handle);
        $pluginSettings = $plugin->getSettings();

        if (!isset($pluginSettings->$key)) {
            return;
        }

        $pluginSettings->$key = $value;
        Craft::$app->plugins->savePluginSettings($plugin, $pluginSettings->toArray());
    }
}