<?php

namespace App\Exports;

use DB_global;
use App\Models\Findtime\ideation_user_post;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class IdeationRawdataExport implements Responsable, FromQuery, WithHeadings, WithMapping, WithStrictNullComparison
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Id',
            'Business Challenge Name',
            'Business Challenge Description',
            'Idea Name',
            'Idea Description',
            'Idea Status',
            'Posting User Alias',
            'Posting User',
            'Posting Date',
            'User Function',
            'Validity From',
            'Validity To',
            'Campaign Type',
            'Challenger Name',
            'Total Comment',
            'Total Like',
            'Participate in hackathon',
            'Idea Category For',
            'ID Posting User',
            'Email Posting User'
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->bc_name,
            $result->bc_description,
            $result->idea_name,
            $result->idea_description,
            $result->idea_status,
            $result->uc_account,
            $result->uc_name,
            $result->posting_date,
            $result->directorate,
            $result->validity_period_from,
            $result->validity_period_to,
            $result->campaign_type,
            $result->challenger_name,
            $result->total_comment,
            $result->total_like,
            $result->hackathon,
            $result->location,
            $result->employee_id,
            $result->topic,
            $result->email
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
        return $query = ideation_user_post::select(
            'ideation_user_post.id',
            'ideation_user_post.idea_name',
            'ideation_user_post.idea_description',
            'ideation_user_post.attachment_filename',
            'ideation_user_post.idea_status',
            'ideation_user_post.posting_date',
            'b.name AS bc_name',
            'b.description AS bc_description',
            'b.validity_period_from',
            'b.validity_period_to',
            'b.campaign_type',
            'b.challenger_name',
            'c.name AS uc_name',
            'c.account AS uc_account',
            'c.email AS email',
            'c.id AS employee_id',
            DB::RAW('ifnull(sub_x.total_comment, 0) AS total_comment'),
            DB::RAW('ifnull(sub_y.total_like, 0) AS total_like'),
            'c.directorate',
            'c.business_unit',
            'ideation_user_post.hackathon',
            'ideation_user_post.location',
        )
        ->leftJoin('ideation_challenge as b', 'ideation_user_post.business_challenge', '=', 'b.id')
        ->leftJoin('users as c', 'ideation_user_post.user_created', '=', 'c.id')
        ->leftJoin(DB::RAW('(SELECT 
            user_post_id, 
            COUNT(id) AS total_comment
            FROM ideation_user_comment
            WHERE status_active = 1
            GROUP BY user_post_id) as sub_x'),
            'sub_x.user_post_id', '=', 'ideation_user_post.id')
        ->leftJoin(DB::RAW('(SELECT 
            user_post_id, 
            COUNT(id) AS total_like
            FROM ideation_user_like
            WHERE flag_like = 1
            GROUP BY user_post_id) as sub_y'),
            'sub_y.user_post_id', '=', 'ideation_user_post.id')
        ->where('ideation_user_post.status_active', '=', 1)
        ->where('ideation_user_post.platform_id', '=', $this->platform_id)
        ->where('b.is_deleted', '=', 0)
        ->when(isset($this->filter_search), 
            function ($query) {
            $query->where('b.name','like', '%'.$this->filter_search.'%')
            ->orWhere('b.description','like', '%'.$this->filter_search.'%')
            ->orWhere('b.campaign_type','like', '%'.$this->filter_search.'%')
            ->orWhere('b.challenger_name','like', '%'.$this->filter_search.'%')
            ->orWhere('c.name','like', '%'.$this->filter_search.'%')
            ->orWhere('c.account','like', '%'.$this->filter_search.'%')
            ->orWhere('ideation_user_post.dea_name','like', '%'.$this->filter_search.'%')
            ->orWhere('ideation_user_post.idea_description','like', '%'.$this->filter_search.'%')
            ;
        })
        ->when(isset($this->filter_period_from) && isset($this->filter_period_to),
            function ($query) {
            $query->whereBetween(DB::raw('convert(ideation_user_post.posting_date, date)'), [$this->filter_period_from, $this->filter_period_to]);
        });
    }
}
