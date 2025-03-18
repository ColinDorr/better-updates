<?php

namespace ColinDorr\CraftcmsBetterUpdates\handlers;

use Craft;
use DateTime;
use DateInterval;

use ColinDorr\CraftcmsBetterUpdates\handlers\Settings;
use ColinDorr\CraftcmsBetterUpdates\handlers\Updates;


class Validations
{
    public static function AllowUpdateTypeValidation(): bool
    {
        $types = Updates::getUpdateValues();
        $updateType = Updates::getHighestUpdateLevel();
        $version_type = Settings::getSettingsVersionType();

        if( 
            $updateType && isset($types[$updateType]) and
            $version_type && isset($types[$version_type]) and
            $types[$updateType] <= $types[$version_type]
         )
        { 
            return true; 
        }

        return false;
    }

    public static function AllowDayOfWeekValidation(): bool
    {
        $currentDay = date('l');
        $day_of_week = Settings::getSettingsDayOfWeek();
        $frequency = Settings::getSettingsFrequency();

        if (
            ($day_of_week && $currentDay && $day_of_week === $currentDay) ||
             $frequency === "Daily"
        )
        {
            return true;
        }

        return false;
    }

    public static function AllowNextPlannedEmailTimestamp(): bool
    {
        $dates = self::getGetDates();
        $next_planned_email_timestamp = Settings::getSettingsNextPlannedEmailTimestamp();
        return $dates["current"] >= $next_planned_email_timestamp;
    }

    public static function getGetDates( $forced_frequency = null ): array
    {
        $day_of_week = Settings::getSettingsDayOfWeek();
        $frequency = $forced_frequency ?? Settings::getSettingsFrequency();
        $currentDay = new DateTime('today 00:01'); // Base time

        // Adjust to the last occurrence of $day_of_week if it's not today
        if (!empty($day_of_week) && date('N', strtotime($day_of_week)) !== (int)$currentDay->format('N')) {
            $currentDay->modify("last " . $day_of_week);
        }

        // Now update $startDate to reflect the adjusted $currentDay
        $startDate = clone $currentDay;

        // Define intervals
        $intervals = [
            'Hourly' => (clone $startDate)->modify('+1 hour')->getTimestamp(),
            'Daily' => (clone $startDate)->modify('+1 day')->getTimestamp(),
            'Weekly' => (clone $startDate)->modify('+1 week')->getTimestamp(),
            'Bi-Weekly' => (clone $startDate)->modify('+2 weeks')->getTimestamp(),
            'Monthly' => (clone $startDate)->modify('+1 month')->getTimestamp(),
        ];

        return [
            "current" => $currentDay->getTimestamp(),
            "Hourly" => $intervals['Hourly'],
            "Daily" => $intervals['Daily'],
            "Weekly" => $intervals['Weekly'],
            "Bi-Weekly" => $intervals['Bi-Weekly'],
            "Monthly" => $intervals['Monthly'] ?? $currentDay->getTimestamp(),
            "selected_frequency" => $intervals[$frequency] ?? $currentDay->getTimestamp()
        ];
    }

    public static function GetErrors(
        bool $forced = false,
        bool $is_critical = false,
    ): array 
    {
        $invalid_checks = [];

        if(!self::AllowUpdateTypeValidation() && !$is_critical || $forced){
            $invalid_checks[] = "Blocked by selected Major|Minor|Patch type";
        }

        if (!self::AllowDayOfWeekValidation() && !$is_critical || $forced) {
            $invalid_checks[] = "Blocked by selected Day";
        }

        if (!self::AllowNextPlannedEmailTimestamp() && !$forced) {
            $invalid_checks[] = "Blocked by selected frequency";
        }
        
        return $invalid_checks;
    }
    
    public static function IsMailingAllowed(
        bool $forced = false,
        bool $is_critical = false,
    ): bool
    {   
        $errors = self::GetErrors($forced);
        return count($errors) === 0 || $forced;
    }

    public static function isCriticalMailingAllowed(
        bool $forced = false,
        bool $is_critical = false,
    ): bool
    {   
        $is_critical = $is_critical || Updates::hasCriticalUpdate();
        $errors = self::GetErrors($forced);
        return count($errors) === 0 || $forced;
    }
}