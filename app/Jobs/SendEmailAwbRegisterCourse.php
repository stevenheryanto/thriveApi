<?php

namespace App\Jobs;

use App\Models\AWB\awb_mst_config;
use DB_global;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_reg_period;
use App\Models\AWB\awb_mst_course;

class SendEmailAwbRegisterCourse implements ShouldQueue
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
            $this->details['course_id'],
            $this->details['toEmail'],
            $this->details['toName'],
            $this->details['platform_id'],
        );
    }

    private function sendEmail($course_id, $toEmail, $toName, $platform_id)
    {
        Log::info('enter sendEmail '. $toName . ' - '. $toEmail);
        try {
            $claim_period = awb_mst_reg_period::where('platform_id', '=', $platform_id)
                ->orderBy('date_modified', 'desc')
                ->value('claim_period');
            $raCourse = awb_mst_course::where('id', '=', $course_id)->first();
            $uploadLink = awb_mst_config::where('_code', '=', 'BUTTON_UPLOAD_REGISTER')->where('platform_id', '=', $platform_id)->value('value');

            $subject = "Satu Langkah Lagi untuk memulai Skills for Future | One More Step to Start Skills For Future";

                $body = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'><html xmlns:v='urn:schemas-microsoft-com:vml'>
                <html>
                <head>
                    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
                    <meta http-equiv='Access-Control-Allow-Origin' content='*'>
                </head>
                <style>
                    body, table, tr, td{
                        font-family:calibri !important;
                        font-size: 11pt;
                    }
                    @font-face
                        {font-family:Calibri;
                        panose-1:2 15 5 2 2 2 4 3 2 4;
                        mso-font-charset:204;
                        mso-generic-font-family:swiss;
                        mso-font-pitch:variable;
                        mso-font-signature:-469750017 -1073732485 9 0 511 0;}
                    /* Style Definitions */
                    p.MsoNormal, li.MsoNormal, div.MsoNormal
                        {mso-style-unhide:no;
                        mso-style-qformat:yes;
                        mso-style-parent:'';
                        margin:0in;
                        margin-bottom:.0001pt;
                        mso-pagination:widow-orphan;
                        font-size:11.0pt;
                        font-family:'Calibri',sans-serif;
                        mso-fareast-font-family:Calibri;
                        mso-fareast-theme-font:minor-latin;}
                    .atas {
                        font-size: 30px;
                    }
                    .isi {
                        padding-left: 10pt;
                    }
                    .h5Button{
                        text-align: center;
                        margin-bottom: 16px;
                        margin-block-start:0;
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
                <body style='font-family:calibri'>
                <p class=MsoNormal align=center style='text-align:center;font-family:calibri;'>
                    <a name='_MailOriginal'></a>
                    <a name='_top'></a>
                    <span style='mso-bookmark:_MailOriginal'>For English Version Click </span>
                    <a href='#english'>
                        <span style='mso-bookmark:_MailOriginal'>Here</span>
                        <span style='mso-bookmark:_MailOriginal'></span>
                    </a>
                    <span style='mso-bookmark:_MailOriginal'><o:p></o:p></span>
                </p>
                <table>
                    <tr>
                    <td class='isi'>
                        <br>
                        Hello @namalengkap,<br>
                        <br>
                        Terima kasih atas ketertarikan Anda dalam mengembangkan diri melalui Skill for Future.
                        <br>
                        Anda selangkah lagi untuk mulai belajar, lakukan langkah dibawah ini untuk menyelesaikan pendaftaran.
                        <br>
                        <br>
                        <ol style='margin-top:0' start=1 type=1>
                            <li>
                                Anda harus melakukan <b>pendaftaran secara mandiri</b> pada halaman provider dengan link dibawah ini:
                            </li>
                        </ol>
                        <table>
                            <tr>
                                <td width=250 style='width:250pt;'>
                                    <p class='h5Button'> Registrasi ke Provider </p>

                                    <div style='text-align:center;'>
                                        <!--[if mso]>
                                            <v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word'
                                                href='@hyperlink_url' style='height:40px;v-text-anchor:middle;width:150px;' arcsize='30%' fillcolor='#ed7d31' strokecolor='#823b0b' strokeweight='1pt'>
                                                <w:anchorlock/>
                                                <center  style='color:#ffffff;font-family:calibri;font-size:12px;font-weight:bold;'>
                                                Registrasi
                                                </center>
                                            </v:roundrect>
                                        <![endif]-->

                                        <a href='@hyperlink_url'
                                        style='background-color:#ed7d31;border:1px solid #823b0b;border-radius:10px;color:#ffffff;display:inline-block;font-family:calibri;font-size:12px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:150px;-webkit-text-size-adjust:none;mso-hide:all;'>
                                            Registrasi
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                            <br>
                        <ol style='margin-top:0' start=2 type=1>
                            <li>
                                Setelah Anda berhasil mendaftar pada provider, kirimkan <b>bukti pendaftaran</b> tersebut pada link dibawah ini:
                                <br>
                                <span style='font-size:9.0pt;'>(bukti pendaftaran dapat berupa bukti bayar atau tangkapan gambar yang membuktikan Anda sudah terdaftar)</span>
                            </li>
                        </ol>
                        <table>
                            <tr>
                                <td width=250 style='width:250pt;'>
                                    <p class='h5Button'> Upload Bukti Registrasi </p>

                                    <div style='text-align:center;'>
                                        <!--[if mso]>
                                            <v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word'
                                                href='@upload_url' style='height:40px;v-text-anchor:middle;width:150px;' arcsize='30%' fillcolor='#ed7d31' strokecolor='#823b0b' strokeweight='1pt'>
                                                <w:anchorlock/>
                                                <center style='color:#ffffff;font-family:calibri;font-size:12px;font-weight:bold;'>Upload</center>
                                            </v:roundrect>
                                        <![endif]-->

                                        <a href='@upload_url'
                                        style='background-color:#ed7d31;border:1px solid #823b0b;border-radius:10px;color:#ffffff;display:inline-block;font-family:calibri;font-size:12px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:150px;-webkit-text-size-adjust:none;mso-hide:all;'>
                                            Upload
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <br>
                        <br>
                        Berikut course yang sudah Anda pilih dalam program Skills For Future:<br>
                        <table style='width:100%'>
                        <tr><td id='left'>Title Course</td><td id='mid'>:</td><td> @title_ind</td></tr>
                        <tr><td id='left'>Provider</td><td id='mid'>:</td><td> @provider</td></tr>
                        <tr><td id='left'>Price</td><td id='mid'>:</td><td>@price</td></tr>
                        <tr><td id='left'>Currency</td><td id='mid'>:</td><td>@typePrice</td></tr>
                        <tr><td id='left'>Course Period</td><td id='mid'>:</td><td> @start_end_date</td></tr>
                        <!-- <tr><td id='left'>Registration Close Date</td><td id='mid'>:</td><td> @in_close_date</td></tr> -->
                        </table>
                        <br>
                        <br>
                        Perusahaan memberikan dukungan atas pengembangan diri melalui Skill for Future Course Berbayar ini berupa penggantian biaya course.<br>
                        <br>
                        Adapun mekanisme untuk penggantian tersebut adalah sebagai berikut:<br>
                        <table>
                            <tr>
                                <td id='mid'>&#8226</td>
                                <td>Telah menyelesaikan keseluruhan course yang Anda pilih di atas dan telah menerima sertifikat dari provider.</td>
                            </tr>
                            <tr>
                                <td id='mid'>&#8226</td>
                                <td>Anda dapat melakukan klaim melalui C&B Reimburse dengan melampirkan scan copy bukti pembayaran dan sertifikat sesuai dengan course yang sudah Anda pilih <b>maksimum tanggal @claimperiod.</b></td>
                            </tr>
                        </table>
                        <br>
                        Untuk informasi lebih lanjut, silakan menghubungi People & Culture melalui iCall Center di ext. 999 Line 2 atau melalui email ke <a href='mailto:YourHR.Asia@pmi.com'>YourHR.Asia@pmi.com</a>.<br>
                        <br>
                        <br>
                        Terima kasih<br>
                        #AdaWaktunyaBelajar
                        <br><br>
                    </td>
                    </tr>

                </table>
                <p class='MsoNormal' align='center' style='text-align:center'>
                    <hr>
                    <o:p></o:p>
                 </p>
                <p class=MsoNormal>
                    <a name=english>
                        <span style='color:white;mso-themecolor:background1'>English Version<o:p></o:p></span>
                    </a>
                </p>

                <span style='mso-bookmark:english'></span>
                <table>
                    <tr>
                    <td class='isi' id='english'>
                        <br>
                        Hello @namalengkap,<br>
                        <br>
                        Thank you for your passion to keep developing yourself through the Skills for Future program.
                        <br>
                        You are one step away from starting the learning, please complete the steps below to complete registration.
                        <br>
                        <br>
                        <ol style='margin-top:0' start=1 type=1>
                            <li>
                                You must <b>register yourself independently</b> on the provider page with the link below
                            </li>
                        </ol>
                        <table>
                            <tr>
                                <td width=250 style='width:250pt;'>
                                    <p class='h5Button'> Register to Provider </p>

                                    <div style='text-align:center;'>
                                        <!--[if mso]>
                                            <v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word'
                                                href='@hyperlink_url' style='height:40px;v-text-anchor:middle;width:150px;' arcsize='30%' fillcolor='#ed7d31' strokecolor='#823b0b' strokeweight='1pt'>
                                                <w:anchorlock/>
                                                <center  style='color:#ffffff;font-family:calibri;font-size:12px;font-weight:bold;'>
                                                Register
                                                </center>
                                            </v:roundrect>
                                        <![endif]-->

                                        <a href='@hyperlink_url'
                                        style='background-color:#ed7d31;border:1px solid #823b0b;border-radius:10px;color:#ffffff;display:inline-block;font-family:calibri;font-size:12px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:150px;-webkit-text-size-adjust:none;mso-hide:all;'>
                                            Register
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                            <br>
                        <ol style='margin-top:0' start=2 type=1>
                            <li>
                                After successfully registered in provider, kindly submit proof of registration on the link below:
                                <br>
                                <span style='font-size:9.0pt;'>(a form of proof registration can be a receipt or screenshot indicating that you are successfully registered)</span>
                            </li>
                        </ol>
                        <table>
                            <tr>
                                <td width=250 style='width:250pt;'>
                                    <p class='h5Button'> Upload The Registration Receipt </p>

                                    <div style='text-align:center;'>
                                        <!--[if mso]>
                                            <v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word'
                                                href='@upload_url' style='height:40px;v-text-anchor:middle;width:150px;' arcsize='30%' fillcolor='#ed7d31' strokecolor='#823b0b' strokeweight='1pt'>
                                                <w:anchorlock/>
                                                <center style='color:#ffffff;font-family:calibri;font-size:12px;font-weight:bold;'>Upload</center>
                                            </v:roundrect>
                                        <![endif]-->

                                        <a href='@upload_url'
                                        style='background-color:#ed7d31;border:1px solid #823b0b;border-radius:10px;color:#ffffff;display:inline-block;font-family:calibri;font-size:12px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:150px;-webkit-text-size-adjust:none;mso-hide:all;'>
                                            Upload
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <br>
                        <br>
                        Here is the details of your course:<br>
                        <table style='width:100%'>
                        <tr><td id='left'>Title Course </td><td id='mid'>:</td><td> @title_eng</td></tr>
                        <tr><td id='left'>Provider</td><td id='mid'>:</td><td> @provider</td></tr>
                        <tr><td id='left'>Price</td><td id='mid'>:</td><td>@price</td></tr>
                        <tr><td id='left'>Currency</td><td id='mid'>:</td><td>@typePrice</td></tr>
                        <tr><td id='left'>Course Period</td><td id='mid'>:</td><td> @start_end_date</td></tr>
                        <!-- <tr><td id='left'>Registration Close Date</td><td id='mid'>:</td><td> @in_close_date</td></tr> -->
                        </table>
                        <br>
                        <br>
                        The company provides support for self-development through Skill for Future Paid Course in the form of course fee reimbursement.<br>
                        <br>
                        Mechanism for reimbursement is as follow:<br>
                        <table>
                            <tr>
                                <td id='mid'>&#8226</td>
                                <td>Have completed the entire course you chose above and have received a certificate from the provider.</td>
                            </tr>
                            <tr>
                                <td id='mid'>&#8226</td>
                                <td>You can claim through C&B Reimburse by attaching a scanned copy of proof of payment and a certificate according to the course you have chosen, latest by <b>@claimperiod.</b></td>
                            </tr>
                        </table>
                        <br>
                        For more information, please contact People & Culture via the iCall Center at ext. 999 Line 2 or by email to <a href='mailto:YourHR.Asia@pmi.com'>YourHR.Asia@pmi.com</a>.<br>
                        <br>
                        <br>
                        Thank You<br>
                        #AdaWaktunyaBelajar
                        <br>
                    </td>
                    </tr>

                </table>
                </body>
                <footer>

                </footer>
                </html>";

            $subject = str_replace('@namalengkap', $toName, $subject);
            $body = str_replace('@namalengkap', $toName, $body);
            $body = str_replace('@title_eng', $raCourse->title, $body);
            $body = str_replace('@title_ind', $raCourse->title_ind, $body);
            $body = str_replace('@desciption_eng', $raCourse->description, $body);
            $body = str_replace('@desciption_ind', $raCourse->description_ind, $body);
            if (strpos($raCourse->hyperlink_url, 'https://') !== false) {
                $body = str_replace('@hyperlink_url', $raCourse->hyperlink_url, $body);
            }
            if (strpos($raCourse->hyperlink_url, 'https://') == false) {
                $body = str_replace('@hyperlink_url', 'https://'.$raCourse->hyperlink_url, $body);
            }

            if (strpos($uploadLink, 'https://') !== false) {
                $body = str_replace('@upload_url', $uploadLink, $body);
            }
            if (strpos($uploadLink, 'https://') == false) {
                $body = str_replace('@upload_url', 'https://'.$uploadLink, $body);
            }

            $date = date_create('1970-01-01');
            if($raCourse->enroll_from <= date_format($date,'Y-m-d')  && $raCourse->enroll_to <= date_format($date,'Y-m-d')){
                $body = str_replace('@start_end_date', "no period", $body);
            } else {
                $body = str_replace('@start_end_date', date('d M Y',strtotime($raCourse->enroll_from)). " - " . date('d M Y', strtotime($raCourse->enroll_to)), $body);
            }
            if($raCourse->close_date <= date_create('1970-01-01')){
                $body = str_replace('@close_date', "", $body);
                $body = str_replace('@in_close_date', "-", $body);
            }	else {
                $body = str_replace('@close_date', " sebelum ". date('d M Y', $raCourse->close_date), $body);
                $body = str_replace('@in_close_date', date('d M Y', $raCourse->close_date), $body);
            }
            $body = str_replace('@provider', $raCourse->provider, $body);
            $body = str_replace('@price', $raCourse->price_amt, $body);
            $body = str_replace('@typePrice', $raCourse->price_type, $body);
            $body = str_replace('@estimate_completion', $raCourse->estimate_completion, $body);
            $body = str_replace('@claimperiod', $claim_period, $body);

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
                'transaction_id' => $course_id);
            DB_global::InsertEmailLog($array_log);
			Log::info('success');
        } catch (\Throwable $th) {
            Log::info('fail: ' .$th);
            Storage::disk('s3')->put('learn/log/'.date('Ymdhms').'.txt', $th, 'public');
        }
    }
}
