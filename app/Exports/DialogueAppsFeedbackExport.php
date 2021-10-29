<?php

namespace App\Exports;

use DB_global;
use App\Models\TTL\dialogue_event;
use App\Models\TTL\dialogue_feedback_user;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DialogueAppsFeedbackExport implements Responsable, FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee ID',
            'Employee Function',
            'Feedback',
            'Reasons',
            'Submit Datetime'
        ];
    }

    public function map($result): array
    {
        return [
            $result->employee_name,
            $result->user_id,
            $result->employee_function,
            (($result->flag_feedback == 1) ? 'Like' : "Don't Like"),
            $result->reason,
            $result->date_feedback
        ];
    }

    public function __construct($platform_id, $filter_search, $filter_period_from, $filter_period_to, $flag_feedback)
    {
        $this->platform_id = $platform_id;
        $this->filter_search = $filter_search;
        $this->filter_period_from = $filter_period_from;
        $this->filter_period_to = $filter_period_to;
        $this->flag_feedback = $flag_feedback;
    }

    public function query()
    {
        return dialogue_feedback_user::query()
        ->select(
            'b.name as employee_name',
            'dialogue_feedback_user.user_id',
            'b.directorate as employee_function',
            'dialogue_feedback_user.reason',
            'dialogue_feedback_user.flag_feedback',
            'dialogue_feedback_user.date_feedback',
            'dialogue_feedback_user.id'
        )
        ->leftJoin('users as b', 'dialogue_feedback_user.user_id', '=', 'b.id')
        ->where('b.status_active', '=', 1)
        ->where('dialogue_feedback_user.platform_id', '=', $this->platform_id)
        ->when(isset($this->flag_feedback), function ($query) {
            $query->where('dialogue_feedback_user.flag_feedback', '=', $this->flag_feedback);
        })
        ->when(isset($this->filter_search), function ($query) {
            $query->where('b.name', 'like', '%'.$this->filter_search.'%')
            ->orWhere('dialogue_feedback_user.user_id', 'like', '%'.$this->filter_search.'%')
            ->orWhere('c.directorate', 'like', '%'.$this->filter_search.'%')
            ->orWhere('dialogue_feedback_user.reason', 'like', '%'.$this->filter_search.'%');
        })
        ->when(isset($this->filter_period_from) && isset($this->filter_period_to), function ($query) {
            $query->whereBetween(DB::raw('convert(dialogue_feedback_user.date_feedback, date)'), [$this->filter_period_from, $this->filter_period_to]);
        });
    }
}
