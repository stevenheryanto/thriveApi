<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use DB_global;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_trn_article;

class SendEmailAwbShareArticle implements ShouldQueue
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
            $this->details['user_id'],
            $this->details['trn_article_id'],
            $this->details['toEmail'],
            $this->details['toName'],
        );
    }

    private function sendEmail($user_id, $trn_article_id, $toEmail, $toName)
    {
        Log::info('enter sendEmail '. $toName . ' - '. $toEmail);
        try{
            $fromName = DB::table('users')->where('id','=',$user_id)->value('name');
            $raArticle = awb_trn_article::selectRaw('md5(id) as md5id, md5(category_id) as md5CategoryId, title, description')
                ->where('id', '=', $trn_article_id)->first();
            $articleUrl = env('FRONTEND_URL_LEARN').'viewall/article?cate='.$raArticle->md5CategoryId.'&articleId='.$raArticle->md5id;

            $subject = "Check this out! Your colleague shared you an interesting content on #AdaWaktunya Belajar";            
            $body = "<html>
            <head>
            <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'></head>
            <html><head>
            <meta http-equiv='Access-Control-Allow-Origin' content='*'>        
            </head>
            <body style='font-family:Calibri,Arial;'>
            <table style='width:100.0%;background:#f4f4f4;border-collapse:collapse' width='100%' cellspacing='0' cellpadding='0' border='0'>
            <tbody>
            <tr>
            <td style='width:7.5pt;padding:0in 0in 0in 0in' width='32'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 0in 0in'>
            <div style='margin-top:22.5pt;margin-bottom:22.5pt'>
                <p class='MsoNormal' style='text-align:center;line-height:150%' align='center'><span style='color:#333333'>
                <img id='logoEmail' src='data:image/png;base64,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' width='231' height='96' ></span><span style='color:#333333'><u></u><u></u></span></p>
            </div>
            </td>
            <td style='width:7.5pt;padding:0in 0in 0in 0in' width='10'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            </tr>	
            <tr>
            <td style='width:7.5pt;padding:0in 0in 0in 0in' width='32'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 0in 0in'>
            <div align='center'>
            <table style='width:100.0%;background:white;border-collapse:collapse;max-width:665px;word-break:break-word' width='100%' cellspacing='0' cellpadding='0' border='0'>
            <tbody>
            <tr>
            <td style='width:11.25pt;padding:0in 0in 7.5pt 0in' width='15'>
            <p class='MsoNormal' style='line-height:150%'><span style='color:#444444'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 7.5pt 0in'>
            <div align='center'>
            <table style='width:100.0%;border-collapse:collapse' width='100%' cellspacing='0' cellpadding='0' border='0'>
            <tbody>
            <tr style='height:7.5pt'>
            <td style='padding:0in 0in 0in 0in;height:7.5pt'>
            <p class='MsoNormal' style='line-height:150%'><span style='color:#444444'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            </tr>
            <tr>
            <td style='padding:0in 0in 0in 0in'>
            <div style='margin-left:39.0pt'>
            <div>
            <p class='MsoNormal' style='line-height:150%'><span style='color:#1f497d'><u></u>&nbsp;<u></u></span></p>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:11.0pt;line-height:150%;font-family:Poppins, sans-serif;color:#1f497d'>Hi @EmailToemployeeName,<u></u><u></u></span></p>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:11.0pt;line-height:150%;font-family:Poppins, sans-serif;color:#1f497d'>@EmailFromemployeeName
            </span><span style='font-size:11.0pt;line-height:150%;font-family:Poppins, sans-serif;color:#444444'>shared
            </span><span style='font-size:11.0pt;line-height:150%;font-family:sans-serif'><a href='@ArticleUrl' target='_blank' ><b><span style='color:#1f497d;text-decoration:none'>@ArticleTitle</span></b></a><span style='color:#444444'>
            with </span><span style='color:#1f497d'>you.<u></u><u></u></span></span></p>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:11.0pt;line-height:150%;font-family:Poppins, sans-serif;color:#1f497d'><u></u>&nbsp;<u></u></span></p>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:11.0pt;line-height:150%;font-family:Poppins, sans-serif;color:#1f497d'>@ArticleTitle<u></u><u></u></span></p>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:11.0pt;line-height:150%;font-family:Poppins, sans-serif;color:#1f497d'>@ArticleDescription<br/><br/><br/></span><span style='font-size:11.0pt;line-height:150%;font-family:Poppins, sans-serif;color:#1f497d'><u></u><u></u></span></p>
            </div>
            </div>
            </td>
            </tr>
            </tbody>
            </table>
            </div>
            </td>
            <td style='width:11.25pt;padding:0in 0in 7.5pt 0in' width='15'>
            <p class='MsoNormal' style='line-height:150%'><span style='color:#444444'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            </tr>
            <tr style='height:7.5pt'>
            <td style='width:7.5pt;padding:0in 0in 0in 0in;height:7.5pt' width='15'>
            <p class='MsoNormal' style='line-height:150%'><span style='color:#444444'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 0in 0in;height:7.5pt'>
            <p class='MsoNormal' style='line-height:150%'><span style='color:#444444'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='width:7.5pt;padding:0in 0in 0in 0in;height:7.5pt' width='15'>
            <p class='MsoNormal' style='line-height:150%'><span style='color:#444444'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            </tr>
            </tbody>
            </table>
            </div>
            </td>
            <td style='padding:0in 0in 0in 0in'></td>
            </tr>
            <tr style='height:17.5pt'>
            <td style='width:7.5pt;padding:0in 0in 0in 0in;height:17.5pt' width='32'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 0in 0in;height:17.5pt'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='width:7.5pt;padding:0in 0in 0in 0in;height:17.5pt' width='10'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            </tr>
            <tr style='height:15.0pt'>
            <td style='width:7.5pt;padding:0in 0in 0in 0in;height:15.0pt' width='32'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='width:100.0%;padding:7.5pt 0in 7.5pt 0in;height:15.0pt' width='100%'>
                <a href='@ArticleUrl' target='_blank' ><p class='MsoNormal' style='text-align:center;line-height:150%' align='center'><span style='color:#333333'><img id='btnEmail' src='data:image/png;base64,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' width='166' height='37' border='0' ></span><span style='color:#333333'><u></u><u></u></span></p></a>
            </td>
            <td style='width:7.5pt;padding:0in 0in 0in 0in;height:15.0pt' width='10'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            </tr>
            <tr>
            <td style='width:7.5pt;padding:0in 0in 0in 0in' width='32'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 0in 0in'>
            <p class='MsoNormal' style='text-align:center' align='center'><span style='font-size:11.0pt;font-family:sans-serif'>Subscribe to our newsletter for new content update every week !<span style='color:#333333'><u></u><u></u></span></span></p>
            </td>
            <td style='padding:0in 0in 0in 0in'></td>
            </tr>
            <tr style='height:7.5pt'>
            <td style='padding:0in 0in 0in 0in;height:7.5pt'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 0in 0in;height:7.5pt'></td>
            <td style='padding:0in 0in 0in 0in;height:7.5pt'></td>
            </tr>
            <tr>
            <td style='width:7.5pt;padding:0in 0in 0in 0in' width='32'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            <td style='padding:0in 0in 15.0pt 0in'></td>
            <td style='width:7.5pt;padding:0in 0in 0in 0in' width='10'>
            <p class='MsoNormal' style='line-height:150%'><span style='font-size:10.5pt;line-height:150%;font-family:Poppins, sans-serif;color:#282828'></span><span style='color:#333333'><u></u><u></u></span></p>
            </td>
            </tr>
            </tbody>
            </table></body></html>";
	
            $body = str_replace('@EmailToemployeeName', $toName, $body);
            $body = str_replace('@EmailFromemployeeName', $fromName, $body);
            $body = str_replace('@ArticleTitle', $raArticle->title, $body);
            $body = str_replace('@ArticleUrl', $articleUrl, $body);
            $body = str_replace('@ArticleDescription', $raArticle->description, $body);

            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->setFrom(env('MAIL_FROM_ADDRESS_LEARN'), env('MAIL_FROM_NAME_LEARN'));
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->Host       = env('MAIL_HOST');
            $mail->Port       = env('MAIL_PORT');
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'tls';
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject    = $subject;
            $mail->Body       = $body;

            $mail->Send();
            $array_log = array('sender_name' => $fromName,
                'subject' => $subject,
                'email_to' => $toEmail,
                'email_body' => $body,
                'flag_email' => 1,
                'date_email' => DB_global::Global_CurrentDatetime(),
                'transaction_id' => $trn_article_id);
            DB_global::InsertEmailLog($array_log);
			Log::info('success');
        } catch (\Throwable $th) {
            Log::info('fail: ' .$th);
            Storage::disk('s3')->put('learn/log/'.date('Ymdhms').'.txt', $th, 'public');
        }

    }
}
