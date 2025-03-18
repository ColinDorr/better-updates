<?php

namespace ColinDorr\CraftcmsBetterUpdates\events;

use Craft;
use craft\web\View;
use yii\base\Event;
use ColinDorr\CraftcmsBetterUpdates\resources\assets\PluginAsset;

class RegisterAssetBundle
{
    public static function register(): void
    {
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_TEMPLATE,
            function () {
                Craft::$app->getView()->registerAssetBundle(PluginAsset::class);
            }
        );

         // Pass session flash messages to JavaScript
         if (! Craft::$app->request->getIsConsoleRequest()) {
            $session = Craft::$app->getSession();
            $successMessage = $session->get('email_success');
            $errorMessage = $session->get('email_error');

            if ($successMessage || $errorMessage) {
                Craft::$app->getView()->registerJs("
                    window.craftToastMessage = {
                        type: '" . ($successMessage ? "success" : "error") . "',
                        text: '" . ($successMessage ?: $errorMessage) . "'
                    };
                ", View::POS_HEAD);

                // Remove session messages after setting them
                $session->remove('email_success');
                $session->remove('email_error');
            }
        }
    }
}