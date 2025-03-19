<?php

declare(strict_types=1);

namespace ColinDorr\BetterUpdates\models;

use craft\base\Model;
use ColinDorr\BetterUpdates\handlers\Settings as SettingsHandler;
use ColinDorr\BetterUpdates\handlers\Updates;

class Settings extends Model
{
    public string $email;
    public string $version_type = "Minor";
    public string $day_of_week = "Monday";
    public string $frequency = "Weekly";
    public int $next_planned_email_timestamp = 0;
    public bool $update_is_critical;

    public function __construct() 
    {
        $this->email = SettingsHandler::getSystemEmail();
        $this->update_is_critical = Updates::hasCriticalUpdate();
    }

    public function rules(): array
    {
        return [
            [["email", "version_type", "day_of_week", "frequency"], 'string'],
            [["next_planned_email_timestamp"], 'integer'], // Added validation rule for integer
        ];
    }
}
