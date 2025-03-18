<?php

namespace ColinDorr\CraftcmsBetterUpdates\handlers;

use Craft;
use yii\console\ExitCode;
use ColinDorr\CraftcmsBetterUpdates\handlers\Validations;

class Notifications
{
    /**
     * Log an error message and display it in the Craft logs, CLI, or control panel.
     */
    public static function ThrowError($msg = null, $msg_console = null)
    {   
        $message = $msg_console ?? $msg;
        if (!$message) {
            return;
        }

        // Log the error for debugging in Craft logs
        Craft::error($message, __METHOD__);

        // Display error message in CLI
        if (Craft::$app->request->getIsConsoleRequest()) {
            fwrite(STDERR, $message."\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Display error in the control panel (if relevant)
        Craft::$app->getSession()->set('email_error', $message);
    }

    /**
     * Log an informational message for debugging purposes.
     */
    public static function LogMessage($msg = null, $msg_console = null): void
    {   
        $message = $msg_console ?? $msg;
        if (!$message) {
            return;
        }

        // Log the message for debugging
        Craft::info($message, __METHOD__);

        // Display log message in CLI
        if (Craft::$app->request->getIsConsoleRequest()) {
            fwrite(STDOUT, $message."\n");
        }
    }

    /**
     * Display a toast notification message in the Craft control panel.
     */
    public static function ToastMessage($key = null, $msg = null): void
    {
        if (!$key || !$msg) {
            return;
        }

        // Set session toast message if not running in CLI
        if (!Craft::$app->request->getIsConsoleRequest()) {
            Craft::$app->getSession()->set($key, $msg);
        }
    }
}