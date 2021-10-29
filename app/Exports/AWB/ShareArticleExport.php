<?php

namespace App\Exports\AWB;

use Throwable;
use App\Models\AWB\awb_trn_article_share;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;

class ShareArticleExport implements Responsable, FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;

    private $fileName = 'report_share_article.xlsx';
    private $writerType = Excel::XLSX;
    private $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function failed(Throwable $th): void
    {
        Storage::disk('s3')->put('learn/log/ShareArticleExport_'.date('Ymdhms').'.txt', $th, 'public');
    }

    public function headings(): array
    {
        return [
            'Id',
            'Title',
            'User Name',
            'User ID',
            'Share To',
            'Share Date',
            'Points',
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->title,
            $result->user_id,
            $result->user_name,
            $result->share_email_to,
            $result->share_date,
            $result->points,
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
        return awb_trn_article_share::query()
            ->select(
                DB::RAW('ifnull(c.name,c.full_name) as user_name'), 'c.account', 'c.id as user_id', 'b.title', 'awb_trn_article_share.*'
                )
                ->leftJoin('awb_trn_article as b', 'b.id', '=', 'awb_trn_article_share.trn_article_id')
                ->join('users as c', 'c.id', '=', 'awb_trn_article_share.user_id')
                ->where('awb_trn_article_share.platform_id', '=', $this->platform_id)
                ->when(isset($this->filter_period_from, $this->filter_period_to),
                    function ($query) {
                    $query->whereBetween(DB::raw('convert(awb_trn_article_share.share_date,date)'), [$this->filter_period_from, $this->filter_period_to]);
                });
    }
}
