<?php

namespace App\Exports;

use DB_global;
use App\Models\TTL\dialogue_event;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DialogueFeedbackExport implements Responsable, FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee ID',
            'Employee Function',
            'Score',
            'Reasons',
            'Submit Datetime',
            'Title',
            'Place',
            'Date',
            'Time'
        ];
    }

    public function map($result): array
    {
        return [
            $result->employee_name,
            $result->user_id,
            $result->employee_function,
            ($result->rating_score > 0 ? $result->rating_score : ''),
            $result->rating_reason,
            $result->rating_date,
            $result->title,
            $result->place,
            $result->schedule_date,
            $result->schedule_time
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
        return dialogue_event::query()
        ->select(
            'dialogue_event.id', 
            'c.name as employee_name', 
            'c.id as user_id', 
            'd.name as initiator_name', 
            'd.id as initiator_id', 
            'b.title',
            'b.place',
            'b.schedule_date',
            'b.schedule_time',
            'b.total_participant',
            'dialogue_event.date_created',
            'c.directorate as employee_function',
            'd.directorate as initiator_function',
            'dialogue_event.flag_check_mark',
            'dialogue_event.email_confirmation_flag',
            'dialogue_event.email_hold_flag',
            'dialogue_event.email_congratulatory_flag',
            'dialogue_event.email_feedback_flag',
            'dialogue_event.rating_reason',
            'dialogue_event.rating_score',
            'dialogue_event.rating_date'
        )
        ->leftJoin('dialogue_event_schedule as b', 'dialogue_event.event_id', '=', 'b.id')
        ->leftJoin('users as c', 'dialogue_event.user_id', '=', 'c.id')
        ->leftJoin('users as d', 'dialogue_event.user_created', '=', 'd.id')
        ->where('dialogue_event.is_deleted', '=', 0)
        ->where('dialogue_event.platform_id', '=', $this->platform_id)
        ->where('dialogue_event.rating_flag', '=', 1)
        ->where('b.is_deleted', '=', 0)
        ->when(isset($this->filter_search), function ($query) {
            $query->where('b.title', 'like', '%'.$this->filter_search.'%')
            ->orWhere('c.name', 'like', '%'.$this->filter_search.'%')
            ->orWhere('d.name', 'like', '%'.$this->filter_search.'%')
            ->orWhere('b.place', 'like', '%'.$this->filter_search.'%');
        })
        ->when(isset($this->filter_period_from) && isset($this->filter_period_to), function ($query) {
            $query->whereBetween(DB::raw('convert(dialogue_event.date_created, date)'), [$this->filter_period_from, $this->filter_period_to]);
        });
    }
}
