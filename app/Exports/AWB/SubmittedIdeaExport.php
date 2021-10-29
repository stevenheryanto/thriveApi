<?php

namespace App\Exports\AWB;

use App\Models\AWB\awb_trn_submit_idea;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Throwable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\Storage;

class SubmittedIdeaExport implements Responsable, FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;

    private $fileName = 'report_submitted_idea.xlsx';
    private $writerType = Excel::XLSX;
    private $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function failed(Throwable $th): void
    {
        Storage::disk('s3')->put('learn/log/SubmittedIdeaExport_'.date('Ymdhms').'.txt', $th, 'public');
    }

    public function headings(): array
    {
        return [
            'Id',
            'User Id',
            'User Account',
            'User Name',
            'User Email',
            'Submitted Date',
            'Message Idea',
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
            $result->submitted_date,
            $result->message_idea,
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
        return awb_trn_submit_idea::query()
            ->select(
                'awb_trn_submit_idea.id', 'b.id as user_id',
                'awb_trn_submit_idea.message_idea', 'awb_trn_submit_idea.date_created as submitted_date',
                'b.name as user_name', 'b.account as user_account', 'b.email as user_email',
                'b.directorate'
            )->join('users as b', 'b.id', '=', 'awb_trn_submit_idea.user_created')
            ->where('awb_trn_submit_idea.platform_id', '=', $this->platform_id)
            ->when(isset($this->filter_period_from, $this->filter_period_to),
                function ($query) {
                $query->whereBetween(DB::raw('convert(awb_trn_submit_idea.date_created, date)'), [$this->filter_period_from, $this->filter_period_to]);
            });
    }
}
