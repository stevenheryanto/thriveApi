<?php

namespace App\Exports;


use DB_global;
use App\Models\TTT\Timetothink_rating;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeetingScoreExport implements Responsable, FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Session Closed Time',
            'Meeting Subject',
            'Meeting Date',
            'Average Meeting Score',
            'Participants',
            'Leader Name',
        ];
    }

    public function map($result): array
    {
        return [
            $result->submit_time,
            $result->subject,
            $result->meeting_date,
            $result->summary_score_rating,
            $result->total_participant,
            $result->user_organizer_name,
             
        ];
    }


    public function __construct($platform_id, $filter_search, $filter_period_from, $filter_period_to)
    {
        $this->platform_id = $platform_id;
        $this->filter_search =$filter_search;
        $this->filter_period_from = $filter_period_from;
        $this->filter_period_to = $filter_period_to;
    }

    public function query()
    {
        
        return Timetothink_rating::query()
            ->select(
            DB::raw("DATE_FORMAT(timetothink_rating.date_created, '%H:%i') submit_time"),
            'timetothink_rating.id',
            'timetothink_rating.subject',
            'timetothink_rating.user_organizer',
            'timetothink_rating.comment',
            'timetothink_rating.score_rating AS summary_score_rating',
            'timetothink_rating.status_enable',
            'timetothink_rating.status_active',
            'timetothink_rating.user_created',
            'timetothink_rating.date_created',
            DB::raw("DATE_FORMAT(timetothink_rating.date_created, '%d %M %Y') meeting_date"),
            'timetothink_rating.user_modified',
            'timetothink_rating.date_modified',
            'timetothink_rating.is_deleted',
            'users.name as user_organizer_name',
            'timetothink_rating_participant.total_participant'
            )
            ->join('users', 'users.id', '=', 'timetothink_rating.user_organizer')
            ->join(DB::raw('(
                select distinct count(user_participant) as total_participant,rating_id,platform_id 
                    from 
                        timetothink_rating_participant 
                    group by rating_id,platform_id
            ) as timetothink_rating_participant'), 'timetothink_rating.id', '=', 'timetothink_rating_participant.rating_id')
            ->WHERE('timetothink_rating.status_active', '=', '1') 
            ->WHERE('timetothink_rating.flag_draft', '=', '0') 
            ->WHERE('timetothink_rating.platform_id', '=', $this->platform_id)
            ->WHERE('timetothink_rating_participant.platform_id', '=', $this->platform_id)
            ->when(!is_null($this->filter_search) , function ($query) {
                $query->where('timetothink_rating.subject','like', '%'.$this->filter_search.'%')
                ->orWhere('timetothink_rating.comment','like', '%'.$this->filter_search.'%')
                ->orWhere('users.name','like', '%'.$this->filter_search.'%')
                ->orWhere('timetothink_rating.score_rating','like', '%'.$this->filter_search.'%');
            })
            ->when(!is_null($this->filter_period_from) && !is_null($this->filter_period_to)  , function ($query) {
                $query->whereBetween(DB::raw('convert(timetothink_rating.date_created, date)'), [$this->filter_period_from, $this->filter_period_to]);
            });
            // ->orderBy('timetothink_rating.id', 'desc');
    }
}
