<?php

namespace ColinDorr\BetterUpdates\handlers;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use Symfony\Component\Yaml\Yaml;

use ColinDorr\BetterUpdates\handlers\Settings;
use ColinDorr\BetterUpdates\handlers\Updates;
use ColinDorr\BetterUpdates\handlers\Notifications;

class Mailing
{
    public static string $PluginName = "Better Updates";
    
    public static function SendMail( string $emailTemplate = null ): array
    {
        /**
         * Send an update notification email.
         */
        $site = Settings::getSiteName();
        $email = Settings::getSettingsEmail() ?? Settings::getSystemEmail() ?? null;
        $subject = "[" . $site . "] " . self::$PluginName;
        $html = $emailTemplate ?? self::getEmailTemplate();

        if (!$email) {
            Notifications::ThrowError("❌ Error: No valid email provided for update notifications.");
            return [
                "status" => "failed",
                "errors" => [ "Provided email was not valid" ]
            ];
        }

        $emailArray = array_map('trim', explode(',', $email));

        $mailer = Craft::$app->getMailer()
            ->compose()
            ->setTo($emailArray)
            ->setSubject($subject)
            ->setHtmlBody($html);
    
        if ($mailer->send()) {
        // $status = true;
        // if ( $status ) {
            Notifications::ToastMessage('email_success', '✅ Success: Updates sent to ' . implode(', ', $emailArray));
            Notifications::LogMessage( "Updates sent to " . implode(', ', $emailArray), __METHOD__ );
            return [
                "status" => "success",
                "errors" => []
            ];
        } 
        else {
            Notifications::Toast('email_error', '❌ Error: Failed to send updates.');
            Notifications::ThrowError("Failed to send email to " . implode(', ', $emailArray), __METHOD__ );
            return [
                "status" => "failed",
                "errors" => [
                    "Failed to send email to " . implode(', ', $emailArray)
                ]
            ];
        }
    }

    /**
     * Generate the complete email template.
     */
    private static function getEmailTemplate(): string 
    {
        $header = self::getEmailHeader();
        $content = self::getEmailContent();
        $footer = self::getEmailFooter();

        return '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
                <head>   
                    <title>' . Settings::getSiteName() . '</title>
                    <meta name="theme-color" content="#ffffff">
                    <link rel="icon" href="{{ siteUrl }}favicon.ico" type="image/x-icon" />
                    <meta name="format-detection" content="telephone=no">
                    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=no;">
                    <meta name="x-apple-disable-message-reformatting">
                    <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8; IE=7; IE=EDGE" />
                    <meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
                </head>

                <body marginwidth="0" marginheight="0" style="padding:0; margin:0">
                    ' .
                    $header .
                    $content .
                    $footer .
                '</body>
            </html>';
    }

    /**
     * Generate the email content with available and up-to-date updates.
     */
    private static function getEmailContent(): string
    {
        $updateInfo = Updates::getAllUpdates();
        $available_update = $updateInfo['available_update'] ?? [];
        $up_to_date = $updateInfo['up_to_date'] ?? [];

        $message = "<div>";
        if (count($available_update) > 0) {
            $message .= "<p style='font-weight:600'>Available update" . (count($available_update) !== 1 ? "s" : "") . "</p><ul>";
            foreach ($available_update as $update) {
                $message .= "<li>" . $update . "</li>";
            }
            $message .= "</ul><br>";
        }

        if (count($up_to_date) > 0) {
            $message .= "<p style='font-weight:600'>Up to date</p><ul>";
            foreach ($up_to_date as $update) {
                $message .= "<li>" . $update . "</li>";
            }
            $message .= "</ul>";
        }

        return $message;
    }

    /**
     * Generate the email header with branding.
     */
    private static function getEmailHeader(): string
    {
        $siteName = Settings::getSiteName();
        return '
        <table style="margin:0; padding:0; width:100%; height:100%" bgcolor="#ffffff" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tbody>
            <tr>
            <td style="margin:0; padding:0; width:100%; height:100%" valign="top" align="center">
                
                <table bgcolor="#e9eef1" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tbody>
                    <tr>
                    <td align="center">
                        <table class="x_m-shell" cellpadding="0" cellspacing="0" border="0" width="600">
                        <tbody>
                            <tr>
                            <td style="width:600px; min-width:600px; font-size:0pt; line-height:0pt; padding:0; margin:0; font-weight:normal" class="x_td">
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td style="font-size:0pt; line-height:0pt; text-align:left; padding-top:40px; padding-bottom:40px;">
                                                <p style="font-weight: 700; color: #e5422b; font-size: 28px; white-space: nowrap; font-style: italic;">
                                                Craft <span style="font-weight: 400;">CMS</span></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            </tr>
                        </tbody>
                        </table>
                    </td>
                    </tr>
                </tbody>
                </table>

                <table cellpadding="0" cellspacing="0" border="0" width="100%" margin="-20px auto 0">
                <tbody>
                    <tr>
                    <td style="font-size:0pt; line-height:0pt; text-align:left; padding-bottom:74px" class="x_img x_pb-74 x_mpb-40" valign="top" width="600">
                        <table class="x_m-shell" cellpadding="0" cellspacing="0" border="0" width="600" margin="-20px auto" align="center">
                        <tbody>
                            <tr>
                            <td style="width:600px; min-width:600px; font-size:0pt; line-height:0pt; padding:0; margin:0; font-weight:normal; border-bottom-right-radius:32px; border-bottom-left-radius:32px" bgcolor="#e9eef1" class="x_td x_rounded-b">
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tbody>
                                    <tr>
                                    <td style="padding:1px; border-radius:32px; box-shadow:0px 10px 35px -5px rgba(0,56,97,0.16)" bgcolor="#ecf0f3" class="x_p-1 x_rounded x_shadow">
                                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tbody>
                                            <tr>
                                            <td style="padding-left:40px; padding-right:40px; padding-top:39px; padding-bottom:40px; border-radius:32px" bgcolor="#ffffff" class="x_px-40 x_pt-39 x_pb-40 x_rounded">
                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tbody>
                                                    <tr>
                                                        <td style="font-size:22px; font-family: Axiforma ,Arial,sans-serif; text-align:left; min-width:auto!important; line-height:36px; color:#110e72; font-weight:bold; padding-bottom:25px" class="x_text-22 x_lh-36 x_c-blue x_fw-b x_pb-25">' . $siteName . ' has available updates,</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="font-size:16px; color:#333333; font-family:Axiforma,Arial,sans-serif; text-align:left; line-height:24px; padding-bottom:25px;">
                                                        ';
    }

    /**
     * Generate the email Footer.
     */
    private static function getEmailFooter(): string
    {
        return '
            </td>
            </tr>
            <tr>
                <td style="font-size:0pt; line-height:0pt; text-align:left; border-top:1px solid #909090; padding-bottom:23px;">&nbsp;</td>
            </tr>
            <tr>
                <td style="font-size:16px; color:#333333; font-family:\'Axiforma\',Arial,sans-serif; text-align:left; line-height:24px;">
                    <p>This email is automatically generated by the Craft CMS plugin ' . self::$PluginName . '.</p>
                </td>
            </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>
        </td>
        <td style="font-size:0pt; line-height:0pt; text-align:left" valign="top" class="x_img">
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tbody>
        <tr>
        <td style="font-size:0pt; line-height:0pt; text-align:left" height="56" bgcolor="#e9eef1" class="x_img x_mpr-16">&nbsp;</td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>';
    }
}