<?php

namespace App\Exports;

use DB_global;
use App\Models\TTL\dialogue_yawa;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class YawaExport implements Responsable, FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Host Name',
            'Host Function',
            'Initiator Name',
            'Initiator ID',
            'Initiator Function',
            'Message',
            'Submit Datetime',
            'Reveal Identity',
            'Check Mark',
            'Notes'
        ];
    }

    public function map($result): array
    {
        return [
            $result->hof_name,
            $result->hof_function,
            $result->initiator_name,
            $result->initiator_id,
            $result->initiator_func,
            $result->message,
            $result->date_created,
            (($result->flag_anonymous == 0) ? 'Yes' : 'No'),
            ($result->flag_check_mark == 0 ? '' : ($result->flag_check_mark == 1 ? 'Done' : 'Cancelled')),
            $result->notes
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
        return dialogue_yawa::query()
        ->select(
            'dialogue_yawa.id', 
            DB::raw("ifnull(b.name, concat(dialogue_yawa.host_hof_other,' (other)')) as hof_name"),
            'b.directorate as hof_function',
            'c.name as initiator_name', 
            'c.id as initiator_id', 
            'c.directorate as initiator_func',
            'dialogue_yawa.message',
            'dialogue_yawa.date_created',
            'dialogue_yawa.flag_anonymous',
            'dialogue_yawa.flag_check_mark',
            'dialogue_yawa.notes'
        )
        ->leftJoin('dialogue_user_hof as b', 'dialogue_yawa.host_hof', '=', 'b.id')
        ->leftJoin('users as c', 'dialogue_yawa.user_created', '=', 'c.id')
        ->where('dialogue_yawa.is_deleted', '=', 0)
        ->where('dialogue_yawa.platform_id', '=', $this->platform_id)
        ->when(isset($this->filter_search), function ($query)  {
            $query->where('b.name', 'like', '%'.$this->filter_search.'%')
            ->orWhere('b.directorate', 'like', '%'.$this->filter_search.'%')
            ->orWhere('c.name', 'like', '%'.$this->filter_search.'%')
            ->orWhere('dialogue_yawa.message', 'like', '%'.$this->filter_search.'%')
            ->orWhere('dialogue_yawa.notes', 'like', '%'.$this->filter_search.'%')
            ;
        })
        ->when(isset($this->filter_period_from) && isset($this->filter_period_to), function ($query) {
            $query->whereBetween(DB::raw('convert(dialogue_yawa.date_created, date)'), [$this->filter_period_from, $this->filter_period_to]);
        });
    }
}
