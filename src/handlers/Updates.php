<?php

namespace ColinDorr\BetterUpdates\Handlers;

use Craft;
use craft\helpers\App;
use Symfony\Component\Yaml\Yaml;

class Updates
{
    public static function getAllUpdates(): array
    {
        $craft_updates = self::getCraftUpdates();
        $plugin_updates = self::getPluginUpdates();
        
        $updates = array_merge($craft_updates, $plugin_updates);

        $available_update = [];
        $up_to_date = [];

        if (! empty($updates)) {
            foreach ($updates as $item) {
                if ($item->update_available) {
                    $available_update[] = self::getUpdateDescription($item);
                } else {
                    $up_to_date[] = self::getUpdateDescription($item);
                }
            }
        }

        return [
            "available_update" => self::orderUpdateResults($available_update),
            "up_to_date" => self::orderUpdateResults($up_to_date),
        ];
    }

    public static function getCraftUpdates(): array
    {
        $craftVersion = Craft::$app->getVersion();
        $updateInfo = Craft::$app->getUpdates()->getUpdates();
        $latestCraftVersion = ! empty($updateInfo->cms->releases) ? $updateInfo->cms->releases[0]->version : $craftVersion;
        $craftLicenseKey = Settings::getCraftLicenseKey();
        $is_abandoned = isset($updateInfo->cms->abandoned) && $updateInfo->cms->abandoned;
        $is_expired = isset($updateInfo->cms->status) && $updateInfo->cms->status !== "eligible";

        $containsCritical = isset($updateInfo->cms->releases) && 
            array_reduce($updateInfo->cms->releases, function($carry, $release) {
                return $carry || (isset($release->critical) && $release->critical === true);
            }, false);

        return [(object) [
            'type' => 'craft',
            'handle' => 'craftcms',
            'name' => 'Craft CMS',
            'version' => $craftVersion,
            'update_available' => $latestCraftVersion !== $craftVersion,
            'update_version' => $latestCraftVersion !== $craftVersion ? $latestCraftVersion : null,
            'update_type' => self::compareVersions($latestCraftVersion, $craftVersion),
            'license_key' => $craftLicenseKey,
            'is_expired' => $is_expired,
            'is_critical' => $containsCritical,
            'is_abandoned' => $is_abandoned,
        ]];
    }

    public static function getPluginUpdates(): array
    {
        $plugins = Craft::$app->getPlugins()->getAllPlugins();
        $updateInfo = Craft::$app->getUpdates()->getUpdates();

        // Load plugins from project.yaml
        $projectYamlFilePath = Craft::getAlias('@config/project/project.yaml');
        $projectYamlPlugins = [];
        if (file_exists($projectYamlFilePath)) {
            $parsedData = Yaml::parseFile($projectYamlFilePath);
            $projectYamlPlugins = $parsedData['plugins'] ?? [];
        }

        // Iterate through all plugins and add plugin objects to the $versions array
        $plugins_updates = [];
        foreach ($plugins as $handle => $plugin) {
            $plugin_handle = $plugin->id;
            $plugin_data = $updateInfo->plugins[$plugin_handle] ?? null ;
            $latestPluginVersion = $plugin_data && !empty($updateInfo->plugins[$plugin_handle]->releases) ? $updateInfo->plugins[$plugin_handle]->releases[0]->version : $plugin->version;
            $is_abandoned = $plugin_data && isset($plugin_data->abandoned) && $plugin_data->abandoned;
            $is_expired = $plugin_data && isset($plugin_data->status) && $plugin_data->status !== "eligible";
            $containsCritical = $plugin_data && !empty($updateInfo->plugins[$plugin_handle]->releases) && !empty(array_filter($updateInfo->plugins[$plugin_handle]->releases, function($release) {
                return $release->critical === true;
            }));

            // Get plugin license key
            $pluginLicenseKey = $projectYamlPlugins[$plugin_handle]['licenseKey'] ?? null;
            if ($pluginLicenseKey && $pluginLicenseKey[0] === "$") {
                $pluginLicenseKey = App::env(ltrim($pluginLicenseKey, '$')) ?? null;
            }

            $plugins_updates[] = (object) [
                'type' => 'plugin',
                'handle' => $plugin_handle,
                'name' => $plugin->name,
                'version' => $plugin->version,
                'update_available' => $latestPluginVersion !== $plugin->version,
                'update_version' => $latestPluginVersion !== $plugin->version ? $latestPluginVersion : null,
                'update_type' => self::compareVersions($latestPluginVersion, $plugin->version),
                'license_key' => $pluginLicenseKey,
                'is_expired' => $is_expired,
                'is_critical' => $containsCritical,
                'is_abandoned' => $is_abandoned,
            ];
        }
        return $plugins_updates;
    }

    public static function getUpdateValues(): array 
    {
        return [
            "Critical" => 0,
            "Major" => 1,
            "Minor" => 2,
            "Patch" => 3,
            "No update" => 4
        ];
    }

    public static function getHighestUpdateLevel($updates = null): string
    {
        $updateValues = self::getUpdateValues();
        $updates = $updates ? $updates : self::getCraftUpdates();
        
        if ($updates === null || empty($updates) || Count($updates) === 0) {
            return null;
        }

        return $updates[0]->update_type;
    }

    public static function hasCriticalUpdate($updates = null) : bool
    {
        $updates = $updates ? $updates : self::getCraftUpdates();
        if ($updates === null || empty($updates) || Count($updates) === 0) {
            return null;
        }
        
        return $updates[0]->is_critical;
    }


    private static function getUpdateDescription($item)
    {
        $is_critical_text = $item->is_critical ? "[Critical]" : "";
        $update_type_text = "[" . $item->update_type . "]" . " ";
        $item_name_text = $item->name . " ";
        $item_version_text = ($item->update_available ? ($item->version . " => " . $item->update_version) : $item->version);
        $item_status_text = $item->is_abandoned ? " (Abandoned)" : ($item->is_expired ? " (Expired)" : "");      

        return $is_critical_text . $update_type_text . $item_name_text . $item_version_text . $item_status_text ;
    }

    private static function orderUpdateResults(array $array)
    {
        // Define custom sort order
        $order = [
            "[Critical]" => 0,
            "[Major]" => 1,
            "[Minor]" => 2,
            "[Patch]" => 3,
            "[No update]" => 4,
        ];

        // Custom comparison function
        usort($array, function ($a, $b) use ($order) {
            // Extract the type from each string (e.g., "[Critical]", "[Minor]", etc.)
            preg_match('/\[(.*?)\]/', $a, $matchesA);
            preg_match('/\[(.*?)\]/', $b, $matchesB);

            $typeA = $matchesA[0] ?? '';
            $typeB = $matchesB[0] ?? '';

            // Compare based on custom order
            return $order[$typeA] <=> $order[$typeB];
        });

        return $array;
    }

    /**
     * Check if a version string is valid (e.g., "1.0.0").
     *
     */
    private static function isValidVersion(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+$/', $version) === 1;  // Convert preg_match result to boolean
    }

    /**
     * Compares two version strings and determines if the update is a patch, minor, or major.
     *
     * @param string $oldVersion The old version string (e.g., "1.0.0").
     * @param string $newVersion The new version string (e.g., "2.1.0").
     * @return string The type of update: "patch", "minor", "major", or "no update" if comparison is not possible.
     */
    private static function compareVersions(string $oldVersion, string $newVersion): string
    {
        // Ensure both versions are valid
        if (! self::isValidVersion($oldVersion) || ! self::isValidVersion($newVersion)) {
            return "No update";
        }

        // Split version strings into arrays
        $oldParts = explode('.', $oldVersion);
        $newParts = explode('.', $newVersion);

        // Parse version numbers into integers
        [$oldMajor, $oldMinor, $oldPatch] = array_map('intval', $oldParts);
        [$newMajor, $newMinor, $newPatch] = array_map('intval', $newParts);

        // Compare versions
        if ($newMajor !== $oldMajor) {
            return "Major";
        } elseif ($newMinor !== $oldMinor) {
            return "Minor";
        } elseif ($newPatch !== $oldPatch) {
            return "Patch";
        }

        return "No update"; // No update if versions are the same
    }
}