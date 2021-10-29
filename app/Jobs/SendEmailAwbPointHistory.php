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
use App\Models\AWB\awb_tmp_point_history;

class SendEmailAwbPointHistory implements ShouldQueue
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
            $this->details['platform_id']
        );
    }

    private function sendEmail($platform_id)
    {
        try{
            $queryHdr = DB::table('awb_tmp_point_history as p')
                ->join('users as u', 'p.user_id', '=', 'u.id')
                ->selectRaw('distinct(p.user_id), u.full_name, u.email')
                ->where('p.platform_id', '=', $platform_id)
                ->get();
            $localUrl = env('FRONTEND_URL_LEARN');
            $imgAttachHdr = file_get_contents(resource_path('image/insert_poin_hdr.jpg'));
            $imgAttachBtn = file_get_contents(resource_path('image/insert_poin_btn.jpg'));
            foreach($queryHdr as $row)
            {
                $queryDtl = awb_tmp_point_history::where('user_id', '=', $row->user_id)
                    ->where('platform_id', '=', $platform_id)
                    ->select('point', 'source')
                    ->get();
                $strTbl = "<table><td class='hd'>Tanggal</td><td class='hd'>Deskripsi</td><td class='hd'>Jumlah Poin</td>";		
                foreach($queryDtl as $rowdtl)
                {	
                    $strTbl = $strTbl . "<tr>";		
                    $strTbl = $strTbl . "<td class='center'>". date("d/m/Y") ."</td>";
                    $strTbl = $strTbl . "<td class='center'>". $rowdtl->source ."</td>";
                    $strTbl = $strTbl . "<td class='center'>". $rowdtl->point ."</td>";
                    $strTbl = $strTbl . "</tr>";		
                }
                $strTbl = $strTbl . "</table>";		
                $toEmail = $row->email;
            
                $subject = "Selamat @namalengkap. Anda mendapatkan tambahan poin.";
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
                        .hd {
                            border-top: 1px solid #6252bd;
                            border-bottom: 1px solid #6252bd;
                            text-align: center;
                            width: 200px;
                        }
                        .center {
                            text-align: center;
                            width: 268px;
                        }
                        a {text-decoration: none;} 
                        .footer {
                            text-align: center;
                            width: 800px;
                        }
                    </style>
                    <body>
                    <table>
                        <tr>
                            <td>
                                <a href='".$localUrl."profile#PointHistory'>
                                <img src='cid:insert_poin_hdr' alt='go to Point History' width='834' height='166' border='0' 
                                style='border:0; outline:none; text-decoration:none; display:block;'>
                                </a>
                            </td>
                        </tr>
                    </table>
                    <div style='font-size: 14px;></div>
                    <table>	
                        <tr>
                        <td class='isi'>
                            <br>
                            <div class='atas'>Selamat ".$row->full_name."!</div>
                            <br>
                            Anda mendapatkan <b>tambahan poin</b> dalam <i><b>#AdaWaktunyaBelajar</b></i><br>
                            Berikut rincian tambahan poin Anda:<br>
                            <br>
                            ".$strTbl."
                            <br>
                            Cek jumlah poin <i><b>#AdaWaktunyaBelajar</b></i> Anda di halaman profile dan tukarkan dengan learning reward special dari kami<br>
                            <br>
                        </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td>
                                <a href='".$localUrl."profile#PointHistory'>
                                <img src='cid:insert_poin_btn' alt='go to Point History' width='834' height='71' border='0' 
                                style='border:0; outline:none; text-decoration:none; display:block;'>
                                </a>
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td valign='top' width='834' style='text-align:center;'>
                            <br>Terima kasih,<br>
                            <b>Learning Team</b><br>
                            </td>
                        </tr>
                    </table>
                    </body>
                    </html>";
                                
                $EmailToemployeeName = $row->full_name;
                $subject = str_replace('@namalengkap', $EmailToemployeeName, $subject);

                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->setFrom(env('MAIL_FROM_ADDRESS_LEARN'), env('MAIL_FROM_NAME_LEARN'));
                $mail->Username   = env('MAIL_USERNAME');
                $mail->Password   = env('MAIL_PASSWORD');
                $mail->Host       = env('MAIL_HOST');
                $mail->Port       = env('MAIL_PORT');
                $mail->SMTPAuth   = true;
                $mail->SMTPSecure = 'tls';
                $mail->addAddress($toEmail, $row->full_name);
                $mail->addStringEmbeddedImage($imgAttachHdr, 'insert_poin_hdr', 'insert_poin_hdr.jpg', 'base64', 'image/png');
                $mail->addStringEmbeddedImage($imgAttachBtn, 'insert_poin_btn', 'insert_poin_btn.jpg', 'base64', 'image/png');
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
                    'transaction_id' => $platform_id);
                DB_global::InsertEmailLog($array_log);
                Log::info('success');
            }
        } catch (\Throwable $th) {
            Log::info('fail: ' .$th);
            Storage::disk('s3')->put('learn/log/'.date('Ymdhms').'.txt', $th, 'public');
        }
    }
}
