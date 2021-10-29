<?php

namespace App\Exports\AWB;

use Throwable;
use App\Models\AWB\awb_trn_email_subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;

class EmailSubscribeExport implements Responsable, FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;

    private $fileName = 'report_email_subscribe.xlsx';
    private $writerType = Excel::XLSX;
    private $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function failed(Throwable $th): void
    {
        Storage::disk('s3')->put('learn/log/EmailSubscribeExport_'.date('Ymdhms').'.txt', $th, 'public');
    }

    public function headings(): array
    {
        return [
            'Id',
            'User Id',
            'User Account',
            'User Name',
            'User Email',
            'User Function',
            'Subscription Date',
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->user_id,
            $result->user_account,
            $result->user_name,
            $result->user_email,
            $result->user_function,
            $result->date_subscription,
        ];
    }

    public function __construct($platform_id, $filter_period_from, $filter_period_to)
    {
        $this->platform_id = $platform_id;
        $this->filter_period_from = $filter_period_from;
        $this->filter_period_to = $filter_period_to;
    }

    public function query()
    {
        return awb_trn_email_subscription::query()
            ->select(
                'awb_trn_email_subscription.id', 'b.id as user_id', 'awb_trn_email_subscription.date_subscription',
		 		'b.name as user_name', 'b.account as user_account', 'b.email as user_email',
				'b.directorate as user_function'
                )
                ->join('users as b', 'b.id', '=', 'awb_trn_email_subscription.id')
                ->where('awb_trn_email_subscription.flag_subscription','=','1')
                ->where('awb_trn_email_subscription.platform_id', '=', $this->platform_id)
                ->when(isset($this->filter_period_from, $this->filter_period_to),
                    function ($query) {
                    $query->whereBetween(DB::raw('convert(awb_trn_email_subscription.date_subscription,date)'), [$this->filter_period_from, $this->filter_period_to]);
                });
    }
}
