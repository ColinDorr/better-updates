<?php

namespace ColinDorr\BetterUpdates\handlers;

use Craft;
use craft\web\Application;

use ColinDorr\BetterUpdates\handlers\Updates;
use ColinDorr\BetterUpdates\handlers\Settings;
use ColinDorr\BetterUpdates\handlers\Notifications;
use ColinDorr\BetterUpdates\handlers\Validations;
use ColinDorr\BetterUpdates\handlers\Mailing;

class HandleUpdateValidations
{
    public static function check(): void
    {       
        // Fetch all available updates
        $updates = Updates::getAllUpdates();

        // Retrieve email notification settings and validation checks
        $frequency = Settings::getSettingsFrequency();
        $isCriticalMailingAllowed = Validations::isCriticalMailingAllowed();
        $isMailingAllowed = Validations::IsMailingAllowed();
        $errors = Validations::GetErrors();

        // Handle critical update notifications
        if ($isCriticalMailingAllowed) {
            // Ensure at least weekly notifications until critical issues are resolved
            $frequency = $frequency !== "Daily" ? "Weekly" : $frequency;
            self::handleSendingMail($frequency);
            return;
        }

        // Handle regular update notifications
        if ($isMailingAllowed) {
            self::handleSendingMail($frequency);
            return;
        }

        // No emails sent, log reasons for blocking notifications
        Notifications::LogMessage("Mailing blocked: " . implode(', ', $errors));
    }

    public static function handleSendingMail($frequency = null) : void
    {
        $response = Mailing::SendMail();

        // If email sending fails, schedule retry in 1 hour
        if($response["status"] === "failed") {
            Settings::setSettingsNextPlannedEmailTimestamp("Hourly"); 
            return; 
        }

        // Update next planned email timestamp based on defined frequency
        Settings::setSettingsNextPlannedEmailTimestamp($frequency);

        return;
    }
}