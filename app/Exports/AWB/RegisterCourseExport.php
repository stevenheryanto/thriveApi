<?php

namespace App\Exports\AWB;

use App\Models\AWB\awb_trn_course;
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
class RegisterCourseExport implements Responsable, FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;

    private $fileName = 'report_register_course.xlsx';
    private $writerType = Excel::XLSX;
    private $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function failed(Throwable $th): void
    {
        Storage::disk('s3')->put('learn/log/RegisterCourseExport_'.date('Ymdhms').'.txt', $th, 'public');
    }

    public function headings(): array
    {
        return [
            'IMDL ID',
            'Name',
            'Course',
            'Provider',
            'Currency',
            'Price',
            'Function',
            'Salary Grade',
            'Year of Service',
            'Created Date',
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->user_name,
            $result->title,
            $result->provider,
            $result->price_type,
            $result->price_amt,
            $result->directorate,
            $result->group_grade,
            $result->group_yos,
            $result->date_created,
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
        return awb_trn_course::query()
            ->select(
                DB::RAW('ifnull(u.name,u.full_name) as user_name'), 'u.id', 'm.title', 'm.provider', 'm.price_type', 
                DB::RAW('case when m.price_amt = 0 then "" else m.price_amt end as price_amt'), 'u.directorate', 
                DB::RAW("(SELECT group_grade FROM awb_users_info WHERE id = awb_trn_course.user_created) as group_grade"),
                DB::RAW("(SELECT group_yos FROM awb_users_info WHERE id = awb_trn_course.user_created) as group_yos"),
                'awb_trn_course.date_created'
                )
                ->leftJoin('awb_mst_course as m', 'm.id', '=', 'awb_trn_course.course_id')
                ->join('users as u', 'u.id', '=', 'awb_trn_course.user_created')
                ->where('awb_trn_course.platform_id', '=', $this->platform_id)
                ->when(isset($this->filter_period_from, $this->filter_period_to),
                    function ($query) {
                    $query->whereBetween(DB::raw('convert(awb_trn_course.date_created,date)'), [$this->filter_period_from, $this->filter_period_to]);
                });
    }
}
