<?php

namespace App\Exports;

use App\Models\TTR\Activity_log;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActivityLogExport implements Responsable, FromQuery, WithHeadings
{
    use Exportable;

    /*
    public function collection()
    {
        return Activity_log::all();
    }
    */
    public function headings(): array
    {
        return [
            'Log Id',
            'User name',
            'User Id Login',
            'User Id',
            'User Email',
            'Access Module',
            'Activity',
            'Access Device',
            'Access Date'
        ];
    }

    public function __construct($access_module, $startDate, $endDate)
    {
        $this->access_module = $access_module;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Activity_log::query()
            ->select('id','user_name','user_account','user_id','user_email','access_module','access_feature','access_device','access_date')
            ->where('access_module', $this->access_module)
            ->when(!is_null($this->startDate), function ($query) {
                return $query->whereBetween('access_date',[ $this->startDate." 00:00:00",$this->endDate." 23:59:59" ]);
            });
    }
}
