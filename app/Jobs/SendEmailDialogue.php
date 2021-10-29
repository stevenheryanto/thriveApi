<?php

namespace App\Jobs;

use DB_global;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class SendEmailDialogue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function handle()
    {
        $this->sendEmail(
            $this->details['md5Id'],
            $this->details['case'],
            $this->details['title'],
            $this->details['scheduleDate'],
            $this->details['time'],
            $this->details['place'],
            $this->details['toEmail'],
            $this->details['toName'],
            $this->details['img_home_menu_gallery'],
            $this->details['img_email_logo']
        );
    }

    private function sendEmail($md5Id, $case, $title, $scheduleDate, $time, $place, $toEmail, $toName, $img_home_menu_gallery, $img_email_logo)
    {
        Log::info('enter sendEmail dialogue');
        $localUrl = env('FRONTEND_URL_LISTEN');
        $thriveLink = $localUrl . '?rating='. $md5Id;
        $path = str_replace('recognition/', '', env('REACT_APP_USER_DOCUMENT'));
        if($img_email_logo){
            Log::info('logoAttach theme');
            $logoAttach = file_get_contents($path.'listen/theme/'.$img_email_logo);
        } else {
            Log::info('logoAttach default');
            $logoAttach = file_get_contents($localUrl.'/_assets/images/default_theme/logo.png');
        }
        if($img_home_menu_gallery){
            Log::info('imgAttach theme');
            $imgAttach = file_get_contents($path.'listen/theme/'.$img_home_menu_gallery);
        } else {
            Log::info('imgAttach default');
            $imgAttach = file_get_contents($localUrl.'/_assets/images/default_theme/feature-gallery.jpg');
        }
        switch($case)
        {
            case 1:
                $emailSubject = "DiaLoGue Confirmation - ". $title ." (". date("F",strtotime($scheduleDate)) .")";       
                $emailContent = "
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: center;font-family:Calibri,Arial;font-size:18px;font-weight:bold'>
                                        Congratulations! We have received your DiaLoGue requests!
                                </div>
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: left;font-family:Calibri,Arial;'>
                                    <br/><br/><br/>
                                    You will have your DiaLoGue sessions with the following details :
                                    <br/><br/>
                                    <b>". $title ."</b><br>
                                    ". date("l, F jS, Y",strtotime($scheduleDate)) ."<br/>
                                    ". $time ."<br/>
                                    ". $place ."<br/>
                                    <br/><br/>
                                    With this we would like you to know the following:<br/>
                                    1) Your travel will be arranged by yourself on Internal Communication cost center, only for <b>same day round trip travel</b>.<br/>
                                    2) Please inform us that you have confirmed the schedule and the conditions above.<br/>
                                    3) After your confirmation we will follow you up on the detail of the venue.<br/>
                                    4) We encourage you to heads up your line manager that you will attend this event.<br/>
                                    <br/>
                                    Thank you!
                                </div>";
                break;
            case 2:
                $emailSubject = 'DiaLoGue Request on Hold';  
                $emailContent = "
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: center;font-family:Calibri,Arial;font-size:18px;font-weight:bold'>
                                        We have received your request. We will keep it on our list.
                                </div>
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: left;font-family:Calibri,Arial;'>
                                    <br/><br/><br/>
                                    Thank you for your requests! Unfortunately, due to the limited seats we cannot facilitate your request for the following session :
                                    <br/><br/>
                                    <b>". $title ."</b><br>
                                    ". date("l, F jS, Y",strtotime($scheduleDate)) ."<br/>
                                    ". $time ."<br/>
                                    ". $place ."<br/>
                                    <br/><br/>
                                    We will still keep your request on our list. We will inform you and book your name first on any update next month on the same schedule.
                                    <br/><br/>
                                    Thank you!
                                </div>";
                break;
            case 3:
                $emailSubject = "DiaLoGue Moments - ". $title ." (". date("F",strtotime($scheduleDate)) .")";   
                $emailContent = "
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: center;font-family:Calibri,Arial;font-size:18px;font-weight:bold'>
                                        You have just created an amazing DiaLoGue moments!
                                </div>
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: left;font-family:Calibri,Arial;'>
                                    <br/><br/><br/>
                                    We hope that you have a great and warm DiaLoGue this ". date("F",strtotime($scheduleDate)) .".
                                    <br/>Your DiaLoGue moment has been posted in Time to Listen Gallery.
                                    <br/><br/>To view your moment, please click the icon below.
                                    <br/>
                                    <br/><br/>
                                </div>
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: center;font-family:Calibri,Arial;font-size:18px;font-weight:bold'>
                                        <a href='". $localUrl . "gallery'><img src='cid:img_home_menu_gallery' style='width:100%'></a>
                                </div>";
                break;
            case 4:
                $emailSubject = "Make DiaLoGue Better for You";   
                $emailContent = "
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: center;font-family:Calibri,Arial;font-size:18px;font-weight:bold'>
                                        We need you to make us better! Please rate your DiaLoGue session.
                                </div>
                                <div style='margin: 0; padding: 0px 0 0 0;text-align: left;font-family:Calibri,Arial;'>
                                    <br/><br/><br/>
                                    Dear friends,
                                    <br/><br/><br/>
                                    I hope you all enjoy the DiaLoGue session that you have attended before. We would like to continue to serve you and other better with this sessions.
                                    <br/>
                                    I would like to ask your favor to give us a rating on how your DiaLoGue session went before.
                                    <br/>
                                    <b>Kindly give us your feedback <a style='font-weight: bold;text-decoration: none' href='". $thriveLink . "'>here</a></b>.
                                    <br/><br/>
                                    This will really help us towards improving DiaLoGue in the future.<br/><br/>
                                    Thank you sincerely.
                                </div>";
                break;
        }

        try {
            $subject = $emailSubject;
            $body = "
            <head>
                <link href='https://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet'>
            </head>
            <body style='font-family:Calibri,Arial; font-size:14px; width:842px'>
                <table>
                    <tr>
                        <td style='text-align:center;'>
                            <img src='cid:img_email_logo' style='width:100%'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <div>
                        <div style='width: 842px;height:700px;' >".$emailContent." </div>
                        </td>
                    </tr>
                </table>
            </body>
            ";
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->Host       = env('MAIL_HOST');
            $mail->Port       = env('MAIL_PORT');
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'tls';

            $mail->addAddress($toEmail, $toName);
            $mail->addStringEmbeddedImage($logoAttach, 'img_email_logo', 'logo.png', 'base64', 'image/png');
            if($case == 3){
                $mail->addStringEmbeddedImage($imgAttach, 'img_home_menu_gallery', 'gallery.png', 'base64', 'image/png');
            }
            $mail->isHTML(true);
            $mail->Subject    = $subject;
            $mail->Body       = $body;

            $mail->Send();
            $array_log = array('sender_name'=>$fromName,
				'subject' => $subject,
				'email_to' => $toEmail,
				'email_body' => $body,
				'flag_email' => 1,
				'date_email' => DB_global::Global_CurrentDatetime(),
				'transaction_id' => $md5Id
            );
			DB_global::InsertEmailLog($array_log);
			Log::info('success');
        } catch (\Throwable $th) {
			Log::info('fail:' .$th);
        }
    }
}
