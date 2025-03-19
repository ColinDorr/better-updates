<?php

namespace ColinDorr\BetterUpdates\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;
use craft\helpers\UrlHelper;
use ColinDorr\BetterUpdates\Plugin;

class SettingsController extends Controller
{
    protected $allowAnonymous = false;

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $plugin = Craft::$app->plugins->getPlugin(Plugin::$plugin_handle);
        $settings = $plugin->getSettings();

        $request = Craft::$app->getRequest();
        $newSettings = [
            'email' => $_POST['email'] ?? $settings->email,
            'version_type' =>$_POST['version_type'] ?? $settings->version_type,
            'day_of_week' => $_POST['day_of_week'] ?? $settings->day_of_week,
            'frequency' => $_POST['frequency'] ?? $settings->frequency,
            'next_planned_email_timestamp' => (int) (isset($_POST['next_planned_email_timestamp']) ? (int)$_POST['next_planned_email_timestamp'] : $settings->next_planned_email_timestamp),
        ];

        if (!Craft::$app->plugins->savePluginSettings($plugin, $newSettings)) {
            Craft::$app->session->setError('Could not save settings.');
            return null;
        }

        Craft::$app->session->setNotice('Settings saved.');
        
        return $this->redirect(UrlHelper::cpUrl(Plugin::$plugin_handle));
    }
}