<?php

namespace ColinDorr\BetterUpdates\events;

use Craft;
use craft\web\Application;
use ColinDorr\BetterUpdates\Plugin;
use ColinDorr\BetterUpdates\handlers\HandleUpdateValidations;

class RegisterUpdateValidations
{
    public static function register(): void
    {    
        if (Craft::$app->request->getIsConsoleRequest()) {
            Craft::$app->controllerMap[Plugin::$plugin_handle] = [
                'class' => \ColinDorr\BetterUpdates\controllers\ConsoleController::class,
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