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

class SendEmailAwbUpdateArticle implements ShouldQueue
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
            $this->details['user_created'],
            $this->details['title'],
            $this->details['description'],
            $this->details['status'],
        );
    }

    private function sendEmail($user_created, $title, $description, $status)
    {
        Log::info('enter sendEmail '. $user_created . ' - '. $status);
        try{
            $query = DB::table('awb_mst_user_profile as m')
                    ->selectRaw('m.id, u.account, u.full_name, u.email, i.group_function')
                    ->leftJoin('users as u', 'u.id', '=', 'm.id')
                    ->leftJoin('awb_users_info as i', 'i.id', '=', 'm.id')
                    ->whereNotNull('date_last_login')
                    ->where([
                        ['u.status_active','=',1],
                        ['m.flag_active','=',1],
                        ['u.id','=', $user_created]
                    ])->first();    
            $toEmail = $query->email;
            $toName = $query->full_name;
            $localUrl = env('FRONTEND_URL_LEARN');

            if($status == 3){
                $status = 'Completed';
                $linktoawb = 'Selamat, konten Anda sudah tayang di <b><i>#AdaWaktunyaBelajar</i></b> dan Anda mendapatkan tambahan +100 poin.<br>Cek konten Anda di <b><i>
                <a href="'.$localUrl.'viewall/page?menu=4e732ced3463d06de0ca9a15b6153677">#AdaWaktunyaBelajar</a></i></b> dan kami sangat menantikan konten Anda berikutnya.';
            }else{
                $status = 'Rejected';
                $linktoawb = 'Mohon maaf konten Anda tidak dapat tayang di <b><i>#AdaWaktunyaBelajar</i></b> karena tidak sesuai dengan kriteria kami. Silahkan melihat informasi konten apa saja yang dapat Anda submit pada halaman <b><i>
                <a href="'.$localUrl.'viewall/page?menu=4e732ced3463d06de0ca9a15b6153677">#AdaWaktunyaBelajar</a></i></b>. Kami sangat menantikan konten Anda berikutnya.';
            }
            $subject = "Konten Anda di #AdaWaktunyaBelajar";

            $body = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns:v='urn:schemas-microsoft-com:vml'>
                <html>
                <head>
                    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
                    <meta http-equiv='Access-Control-Allow-Origin' content='*'>
                </head>
                <style>
                    .atas {
                        font-size: 30px;
                    }
                    .isi {
                        padding-left: 10pt;
                    }
                    #left {
                        width: 23%;
                        white-space:nowrap;
                        vertical-align: top;
                    }
                    #mid {
                        width: 2%;
                        vertical-align: top;
                    }
                </style>
                <body>
                <table>
                    <tr>
                    </tr>
                </table>
                <table>	
                    <tr>
                    <td class='isi'>
                        <br>
                        Hi @namalengkap,<br>
                        <br>
                        Terima kasih atas keikutsertaan Anda mengirimkan konten ke <b><i>#AdaWaktunyaBelajar</i></b> dengan detail:<br><br>
                        <table style='width:100%'>
                        <tr><td id='left'>Judul</td><td id='mid'>:</td><td> @Title</td></tr>
                        <tr><td id='left'>Deskripsi</td><td id='mid'>:</td><td> @Desc</td></tr>
                        </table>
                        <br>
                        ".$linktoawb."
                        <br>
                        <br>
                        Terima kasih,<br>
                        <b><i>Learning Team</i></b>
                        <br>
                    </td>
                    </tr>

                </table>
                </body>
                </html>";

            $subject = str_replace('@Title', $title, $subject);	

            $body = str_replace('@namalengkap', $toName, $body);
            $body = str_replace('@Title', $title, $body);
            $body = str_replace('@Desc', $description, $body);
            $body = str_replace('@status', $status, $body);

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
            $array_log = array('sender_name' => env('MAIL_FROM_NAME_LEARN'),
                'subject' => $subject,
                'email_to' => $toEmail,
                'email_body' => $body,
                'flag_email' => 1,
                'date_email' => DB_global::Global_CurrentDatetime(),
                'transaction_id' => 0);
            DB_global::InsertEmailLog($array_log);
			Log::info('success');
        } catch (\Throwable $th) {
            Log::info('fail: ' .$th);
            Storage::disk('s3')->put('learn/log/'.date('Ymdhms').'.txt', $th, 'public');
        }

    }
}
