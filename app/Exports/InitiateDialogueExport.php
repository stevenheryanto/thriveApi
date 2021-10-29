<?php

namespace App\Exports;

use DB_global;
use App\Models\TTL\dialogue_make_your_own;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InitiateDialogueExport implements Responsable, FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Host Name',
            'Host Function',
            'Employee Name',
            'Employee ID',
            'Employee Function',
            'Initiator Name',
            'Initiator ID',
            'Initiator Function',
            'Topic',
            'Submit Datetime',
            'Check Mark'
        ];
    }

    public function map($result): array
    {
        return [
            $result->name,
            $result->directorate,
            $result->employee_name,
            $result->user_id,
            $result->employee_function,
            $result->initiator_name,
            $result->initiator_id,
            $result->initiator_function,
            $result->topic,
            $result->date_created,
            ($result->flag_check_mark == 0 ? '' : ($result->flag_check_mark == 1 ? 'Done' : 'Cancelled')),
        ];
    }


    public function __construct($platform_id, $filter_search, $filter_period_from, $filter_period_to)
    {
        $this->platform_id = $platform_id;
        $this->filter_search = $filter_search;
        $this->filter_period_from = $filter_period_from;
        $this->filter_period_to = $filter_period_to;
    }

    public function query()
    {
        return dialogue_make_your_own::query()
        ->select(
            'dialogue_make_your_own.id',
            'b.name',
            'b.directorate',
            'c.name as employee_name', 
            'c.id as user_id', 
            'c.directorate as employee_function',
            'd.name as initiator_name', 
            'd.id as initiator_id', 
            'd.directorate as initiator_function',
            'dialogue_make_your_own.topic',
            'dialogue_make_your_own.date_created',
            'dialogue_make_your_own.flag_check_mark'
            )
        ->leftJoin('dialogue_user_hof as b', 'dialogue_make_your_own.host_hof', '=', 'b.id')
        ->leftJoin('users as c', 'dialogue_make_your_own.user_id', '=', 'c.id')
        ->leftJoin('users as d', 'dialogue_make_your_own.user_created', '=', 'd.id')
        ->where('dialogue_make_your_own.is_deleted', '=', 0)
        ->where('dialogue_make_your_own.platform_id', '=', $this->platform_id)
        ->when(isset($this->filter_search), function ($query) {
            $query->where('b.name','like', '%'.$this->filter_search.'%')
            ->orWhere('c.name','like', '%'.$this->filter_search.'%')
            ->orWhere('d.name','like', '%'.$this->filter_search.'%')
            ->orWhere('b.directorate','like', '%'.$this->filter_search.'%');
        })
        ->when(isset($this->filter_period_from) && isset($this->filter_period_to), function ($query) {
            $query->whereBetween(DB::raw('convert(dialogue_make_your_own.date_created, date)'), [$this->filter_period_from, $this->filter_period_to]);
        });
    }
}
