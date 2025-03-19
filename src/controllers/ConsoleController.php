<?php

namespace ColinDorr\BetterUpdates\controllers;

use Craft;
use yii\console\Controller;
use yii\console\ExitCode;

use ColinDorr\BetterUpdates\handlers\Updates;
use ColinDorr\BetterUpdates\handlers\Mailing;
use ColinDorr\BetterUpdates\handlers\Validations;
use ColinDorr\BetterUpdates\handlers\Settings;
use ColinDorr\BetterUpdates\handlers\HandleUpdateValidations;

class ConsoleController extends Controller
{


    /**
     * @var bool Whether to force the update check
     */
    public bool $force = false;

    /**
     * Define available options for the command
     *
     * @return array
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['force']);
    }

    /**
     * Run Plugin and check for CMS and Plugin updates and status
     *
     * Usage:
     * - `php craft better-updates/check` // Check variables against plugin settings and return list with CMS Plugin updates / statuses if validations pass.
     * - `php craft better-updates/check --force` // force approve plugin settings check and list with CMS Plugin updates / statuses if validations pass.
     *
     * @return int Exit code
     */
    public function actionCheck(): int
    {
        $updateInfo = Updates::getAllUpdates();
        $available_update = $updateInfo['available_update'] ?? [];
        $up_to_date = $updateInfo['up_to_date'] ?? [];


        // Log content
        $this->stdout("\n------------------------------------------------------------------------------------------\n");
        if (!empty($available_update)) {
            $this->stdout("Available updates:\n-------------------------\n");
            foreach ($available_update as $item) {
                $this->stdout($item . "\n");
            }
        }

        if (!empty($available_update) && !empty($up_to_date)) {
            $this->stdout("\n\n");
        }

        if (!empty($up_to_date)) {
            $this->stdout("Up to date:\n-------------------------\n");
            foreach ($up_to_date as $item) {
                $this->stdout($item . "\n");
            }
        }

        // Mailing
        if (
            Validations::isCriticalMailingAllowed() || 
            Validations::IsMailingAllowed() 
        ) {
            HandleUpdateValidations::handleSendingMail("Hourly");
            $this->stdout("Email send" . "\n");
        } else if ($this->force) {
            HandleUpdateValidations::handleSendingMail("Hourly");
            $this->stdout("Forced is active" . "\n");
            $this->stdout("Email send" . "\n");
        }

        $this->stdout("------------------------------------------------------------------------------------------\n");
        return ExitCode::OK;
    }

    /**
     * Send a test notification email from the console.
     *
     * Usage:
     * - `php craft better-updates/test-notify`
     *
     * @return array Status of the email notification.
     */
    public function actionTestNotify(): int
    {
        $result = Mailing::SendMail("[Console] => Test notification successful");
        $this->stdout(print_r($result, true) . "\n");
        return ExitCode::OK;
    }

    /**
     * Run a test of the update validation logic.
     *
     * Usage:
     * - `php craft better-updates/test-validation`
     *
     * @return array Validation status and update levels.
     */
    public function actionTestValidation(): int
    {
        $dates = Validations::getGetDates();
        $is_critical = Updates::hasCriticalUpdate();
        $result = [
            
            "validation" => [
                "is_critical" => (bool) $is_critical ? "true" : "false",
                "validation_passed" => (bool) ($is_critical ? Validations::isCriticalMailingAllowed() : Validations::IsMailingAllowed()) ? "true" : "false",
                "errors" => Validations::GetErrors(false, $is_critical),
            ]
        ];

        $this->stdout(print_r($result, true) . "\n");
        return ExitCode::OK;
    }
}