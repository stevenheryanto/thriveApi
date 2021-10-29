<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*require 'vendor/autoload.php';*/

class TestController extends Controller
{
    public function sendemail()
    {
        /*require 'vendor/autoload.php';*/

        $recipient = 'steven.heryanto@contracted.sampoerna.com';
        $subject = 'Local test (SMTP interface accessed using PHP)';
        $bodyText =  "Email Test\r\nThis email was sent through the
            Amazon SES SMTP interface using the PHPMailer class.";

        $bodyHtml = '<h1>Email Test</h1>
            <p>This email was sent through the
            <a href="https://aws.amazon.com/ses">Amazon SES</a> SMTP
            interface using the <a href="https://github.com/PHPMailer/PHPMailer">
            PHPMailer</a> class.</p>';
        /*$configurationSet = 'ConfigSet';*/

        $mail = new PHPMailer(true);

        try {
            // Specify the SMTP settings.
            $mail->isSMTP();
            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->Host       = env('MAIL_HOST');
            $mail->Port       = env('MAIL_PORT');
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'tls';
            /*$mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);*/
            $mail->addAddress($recipient);

            $mail->isHTML(true);
            $mail->Subject    = $subject;
            $mail->Body       = $bodyHtml;
            $mail->AltBody    = $bodyText;
            $mail->Send();
            echo "Email sent!" , PHP_EOL;
        } catch (Exception $e) {
            echo "Email not sent. {$mail->ErrorInfo}", PHP_EOL; //Catch errors from Amazon SES.
        }

    }

}
