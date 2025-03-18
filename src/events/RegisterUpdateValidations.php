<?php

namespace ColinDorr\CraftcmsBetterUpdates\events;

use Craft;
use craft\web\Application;
use ColinDorr\CraftcmsBetterUpdates\Plugin;
use ColinDorr\CraftcmsBetterUpdates\handlers\HandleUpdateValidations;

class RegisterUpdateValidations
{
    public static function register(): void
    {    
        if (Craft::$app->request->getIsConsoleRequest()) {
            Craft::$app->controllerMap[Plugin::$plugin_handle] = [
                'class' => \ColinDorr\CraftcmsBetterUpdates\controllers\ConsoleController::class,
            ];
        }
        else {
            Craft::$app->on(Application::EVENT_INIT, function () {
                HandleUpdateValidations::check();
            });
        }
        return;
    }
}