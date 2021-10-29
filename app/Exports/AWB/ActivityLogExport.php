<?php

namespace App\Exports\AWB;

use Throwable;
use App\Models\AWB\awb_trn_log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;

class ActivityLogExport implements Responsable, FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;

    private $fileName = 'report_activity_log.xlsx';
    private $writerType = Excel::XLSX;
    private $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function failed(Throwable $th): void
    {
        Storage::disk('s3')->put('learn/log/ActivityLogExport_'.date('Ymdhms').'.txt', $th, 'public');
    }

    public function headings(): array
    {
        return [
            'Log Id',
            'User Id',
            'User Account',
            'User Name',
            'User Email',
            'Title',
            'Function',
            'Access Date',
            'Access Device',
            'Activity Type',
            'Activity Info',
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
            $result->user_title,
            $result->user_function,
            $result->log_date,
            $result->access_device,
            $result->log_type,
            $result->log_info . ($result->log_type  == 'Article' ? ' '. $result->category_title .'<br>'. $result->article_title : ''),
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
        return awb_trn_log::query()
            ->select(
                'awb_trn_log.*',
                'b.title as article_title',
                DB::RAW("(SELECT title  
                    FROM awb_trn_category 
                    WHERE id = b.category_id) as category_title"),
                'd.title as user_title',
                'd.directorate as user_function'
            )
            ->leftJoin('awb_trn_article as b', 'b.article_id', '=', 'awb_trn_log.transaction_id')
            ->leftJoin('users as d', 'd.id', '=', 'awb_trn_log.user_id')
            ->where('awb_trn_log.platform_id', '=', $this->platform_id)
            ->when(isset($this->filter_period_from, $this->filter_period_to),
                function ($query) {
                $query->whereBetween(DB::raw('convert(log_date, date)'), [$this->filter_period_from, $this->filter_period_to]);
            });
    }
}
