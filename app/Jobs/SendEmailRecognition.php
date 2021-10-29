<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use DB_global;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Aws\S3\Exception\S3Exception;

class SendEmailRecognition implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function handle()
    {
        $rsEmail = DB_global::RsEmailNotificationByPostId($this->details['id_header'], $this->details['platform_id']);
        Log::info('enter handle');
        foreach($rsEmail as $drow)
        {
            $this->sendEmailNotification($this->details['id_header'],$drow->sender_name,$drow->sender_email,$drow->receiver_name,$drow->receiver_email,$drow->spv_email,$drow->total,$this->details['img_email_notification'],$this->details['color_mail_name'],$this->details['txt_subject_email_notification']);
        }
    }

    private function sendEmailNotification($postId,$fromName,$fromEmail,$toName,$toEmail,$spvEmail,$totalPoint,$img_email_notif,$color_mail_name,$txt_subject_email_notification)
    {
        Log::info('enter sendEmailNotification '. $toName . ' - '. $toEmail);
        try {

        if($this->details['platform_id']==1 || $this->details['platform_id']=="1"){
          $mail_from_address = env('MAIL_FROM_ADDRESS_GRATITUDE');
          $mail_from_name = env('MAIL_FROM_NAME_GRATITUDE');
          $localUrl = env('FRONTEND_URL_RECOGNITION_GRATITUDE');
        } elseif($this->details['platform_id']==8 || $this->details['platform_id']=="8"){
          $mail_from_address = env('MAIL_FROM_ADDRESS_CULTURE');
          // $mail_from_name = env('MAIL_FROM_NAME_FINANCE');
          $mail_from_name = 'LetsRecognize';
          $localUrl = env('FRONTEND_URL_RECOGNITION_CULTURE');
        } else {
          $mail_from_address = env('MAIL_FROM_ADDRESS_CULTURE');
          $mail_from_name = env('MAIL_FROM_NAME_CULTURE');
          $localUrl = env('FRONTEND_URL_RECOGNITION_CULTURE');
        }
        $subjectReplaceTarget = ['<name>','<point>'];
        $subjectReplacedBy = [$toName,$totalPoint];
        $resultReplace = str_replace($subjectReplaceTarget,$subjectReplacedBy,$txt_subject_email_notification);
        // $subject = $resultReplace || "Hi ".$toName.", You got " . $totalPoint ." points !";
        $subject =  $resultReplace;
        // $localUrl = env('FRONTEND_URL_RECOGNITION');
        $thriveLink = $localUrl . '?type=received&email='. md5($postId);
        $docPath = env('REACT_APP_USER_DOCUMENT').'theme/'.$img_email_notif;
        $colorMailName = $color_mail_name=='' ||$color_mail_name==null? '#5E1037' : $color_mail_name;
        
        // Log::info('resource_path: ' .resource_path());
        $imgPath = file_get_contents($docPath);
        $img = Image::make($imgPath);  
        // $img->text($fromName, 401, 388, 
        $img->text($fromName, 401, 360, 
            function($font) use($colorMailName){  
                $font->file(resource_path('font/Lato-Bold.ttf'));
                $font->size(30);  
                $font->color($colorMailName);  
                $font->align('center');  
                $font->valign('bottom');  
                $font->angle(0);  
        });  
        // $img->save(env('REACT_APP_USER_DOCUMENT').'temp/temp.jpg'); 
        $resource = $img->stream()->detach();
        $fileName = DB_global::cleanFileName('temp_'.$fromName.'.png');
        try {
            // $pathB = Storage::disk('local')->put('temp/'.$fileName, $resource, 'public');
            // $path = Storage::disk('local')->path('temp/'.$fileName);
            // Log::info('local: ' .$path);
            $pathS3 = Storage::disk('s3')->put('recognition/temp/'.$fileName, $resource, 'public');
            Log::info('s3: '.$pathS3);
        } catch (S3Exception $e) {
            Log::info('Failed to upload '. $e);
              $array_log = array('sender_name'=>'fail to upload',
              'subject'=>'subject',
              'email_to'=>'toEmail',
              'email_body'=> $e,
              'flag_email'=>1,
              'date_email'=>DB_global::Global_CurrentDatetime(),
              'transaction_id'=>$postId);
            DB_global::InsertEmailLog($array_log);
        }
        // $imgAttach = file_get_contents($path); 
        $imgAttach = file_get_contents(env('REACT_APP_USER_DOCUMENT').'temp/'.$fileName); 
        // $imgAttach = file_get_contents($docPath);
        $body = "
        <!doctype html>
        <html>
          <head>
            <meta name='viewport' content='width=device-width'>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            <title>Simple Transactional Email</title>
            <style>
            /* -------------------------------------
                INLINED WITH htmlemail.io/inline
            ------------------------------------- */
            /* -------------------------------------
                RESPONSIVE AND MOBILE FRIENDLY STYLES
            ------------------------------------- */
            @media only screen and (max-width: 620px) {
              table[class=body] h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
              }
              table[class=body] p,
                    table[class=body] ul,
                    table[class=body] ol,
                    table[class=body] td,
                    table[class=body] span,
                    table[class=body] a {
                font-size: 16px !important;
              }
              table[class=body] .wrapper,
                    table[class=body] .article {
                padding: 10px !important;
              }
              table[class=body] .content {
                padding: 0 !important;
              }
              table[class=body] .container {
                padding: 0 !important;
                width: 100% !important;
              }
              table[class=body] .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
              }
              table[class=body] .btn table {
                width: 100% !important;
              }
              table[class=body] .btn a {
                width: 100% !important;
              }
              table[class=body] .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
              }
            }
    
            /* -------------------------------------
                PRESERVE THESE STYLES IN THE HEAD
            ------------------------------------- */
            @media all {
              .ExternalClass {
                width: 100%;
              }
              .ExternalClass,
                    .ExternalClass p,
                    .ExternalClass span,
                    .ExternalClass font,
                    .ExternalClass td,
                    .ExternalClass div {
                line-height: 100%;
              }
              .apple-link a {
                color: inherit !important;
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                text-decoration: none !important;
              }
              #MessageViewBody a {
                color: inherit;
                text-decoration: none;
                font-size: inherit;
                font-family: inherit;
                font-weight: inherit;
                line-height: inherit;
              }
              .btn-primary table td:hover {
                background-color: #34495e !important;
              }
              .btn-primary a:hover {
                background-color: #34495e !important;
                border-color: #34495e !important;
              }
            }
            </style>
          </head>
          <body class='' style='background-color: ##ffffff; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;'>
            <span class='preheader' style='color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;'>This is preheader text. Some clients will show this text as a preview.</span>
            <table border='0' cellpadding='0' cellspacing='0' class='body' style='border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: ##ffffff;'>
              <tr>
                <td style='font-family: sans-serif; font-size: 14px; vertical-align: top;'>&nbsp;</td>
                <td class='container' style='font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; Margin: 0 auto; max-width: 580px; padding: 10px; width: 580px;'>
                  <div class='content' style='box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px; padding: 10px;'>
    
                    <!-- START CENTERED WHITE CONTAINER -->
                    <table class='main' style='border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #ffffff; border-radius: 3px;'>
    
                      <!-- START MAIN CONTENT AREA -->
                      <tr>
                        <td class='wrapper' style='font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;'>
                          <table border='0' cellpadding='0' cellspacing='0' style='border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;'>
                            <tr>
                              <td style='font-family: sans-serif; font-size: 14px; vertical-align: top;'>
                                <a href='". $thriveLink ."'>
                                <img src='cid:my-attach' alt='Useful alt text' width='537' height='585' border='0' 
                                style='border:0; outline:none; text-decoration:none; display:block;'>
                                </a>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
    
                    <!-- END MAIN CONTENT AREA -->
                    </table>
    
                  <!-- END CENTERED WHITE CONTAINER -->
                  </div>
                </td>
                <td style='font-family: sans-serif; font-size: 14px; vertical-align: top;'>&nbsp;</td>
              </tr>
            </table>
          </body>
        </html>
        ";

        $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->setFrom($mail_from_address, $mail_from_name);
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->Host       = env('MAIL_HOST');
            $mail->Port       = env('MAIL_PORT');
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'tls';
            /*$mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);*/
            $mail->addAddress($toEmail, $toName);
            $mail->addStringEmbeddedImage($imgAttach, 'my-attach', 'recognize.png', 'base64', 'image/png');
            $mail->isHTML(true);
            $mail->Subject    = $subject;
            $mail->Body       = $body;
            if($spvEmail != ''){
                $mail->AddCC($spvEmail);
            }

            $mail->Send();
            $array_log = array('sender_name'=>$fromName,
              'subject'=>$subject,
              'email_to'=>$toEmail,
              'email_body'=>$body,
              'flag_email'=>1,
              'date_email'=>DB_global::Global_CurrentDatetime(),
              'transaction_id'=>$postId);
            DB_global::InsertEmailLog($array_log);
			  Log::info('success');
      } catch (\Throwable $th) {
			  Log::info('fail: ' .$th);
        $array_log = array('sender_name'=>'fail',
        'subject'=>'$subject',
        'email_to'=>'email_to',
        'email_body'=>$th,
        'flag_email'=>1,
        'date_email'=>DB_global::Global_CurrentDatetime(),
        'transaction_id'=>$postId);
        DB_global::InsertEmailLog($array_log);
      }

    }
}
