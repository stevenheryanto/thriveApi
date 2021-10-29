<?php

namespace App\Exports\AWB;

use Throwable;
use DB_global;
use App\Models\AWB\awb_trn_quiz_user;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;

class AnsweredQuizExport implements Responsable, FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;

    private $fileName = 'report_answered_quiz.xlsx';
    private $writerType = Excel::XLSX;
    private $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function failed(Throwable $th): void
    {
        Storage::disk('s3')->put('learn/log/AnsweredQuizExport_'.date('Ymdhms').'.txt', $th, 'public');
    }

    public function headings(): array
    {
        return [
            'Id',
            'Article',
            'Question',
            'Answer',
            'User Name',
            'Answered By User',
            'Answer Date',
            'Answered Result',
            'Points',
        ];
    }

    public function map($result): array
    {
        $quiz_answer= '';
        if($result->answer_mode == 3)
        {
            if(strpos($result->answer_choice_mode_3,'A') !== false)
                $quiz_answer = str_replace('~',', ',$quiz_answer) . 'a) '.DB_global::StringReadMore($result->choice_1,50).'~';
            if(strpos($result->answer_choice_mode_3,'B') !== false)
                $quiz_answer = str_replace('~',', ',$quiz_answer) . 'b) '.DB_global::StringReadMore($result->choice_2,50).'~';
            if(strpos($result->answer_choice_mode_3,'C') !== false)
                $quiz_answer = str_replace('~',', ',$quiz_answer) . 'c) '.DB_global::StringReadMore($result->choice_3,50).'~';
            if(strpos($result->answer_choice_mode_3,'D') !== false)
                $quiz_answer = str_replace('~',', ',$quiz_answer) . 'd) '.DB_global::StringReadMore($result->choice_4,50).'~';
            
            $quiz_answer = str_replace('~','',$quiz_answer);
            $user_answer = $result->answer_choice_mode_3;
        }
        else
        {
            $quiz_answer = $result->quiz_answer;
            $user_answer = $result->user_answer;
        }
        return [
            $result->id,
            $result->article_title,
            $result->question,
            $quiz_answer,
            $result->user_name,
            $user_answer,
            $result->date_modified,
            $result->quiz_result,
            $result->point,
        ];
    }

    public function __construct($platform_id, $filter_period_from, $filter_period_to, $filter_category, $category_iqos_delivery )
    {
        $this->platform_id = $platform_id;
        $this->filter_period_from = $filter_period_from;
        $this->filter_period_to = $filter_period_to;
        $this->filter_category = $filter_category;
        $this->category_iqos_delivery = $category_iqos_delivery;
    }

    public function query()
    {
        return awb_trn_quiz_user::query()->select(
            'awb_trn_quiz_user.id', 'b.question', 'b.question_ind', 'b.answer_mode', 'awb_trn_quiz_user.point', 'awb_trn_quiz_user.date_modified',
            'c.id as user_id', 'c.account', DB::RAW('ifnull(c.name,c.full_name) as user_name'),
            DB::RAW('case when 1 = awb_trn_quiz_user.answer_choice_idx then b.choice_1
                when 2 = awb_trn_quiz_user.answer_choice_idx then b.choice_2
                when 3 = awb_trn_quiz_user.answer_choice_idx then b.choice_3
                when 4 = awb_trn_quiz_user.answer_choice_idx then b.choice_4 end as quiz_answer'),
                'b.choice_1',
                'b.choice_2',
                'b.choice_3',
                'b.choice_4',
                'b.answer_choice_mode_3',
            DB::RAW('case when 1 = awb_trn_quiz_user.answer_flag_idx then b.choice_1
                when 2 = awb_trn_quiz_user.answer_flag_idx then b.choice_2
                when 3 = awb_trn_quiz_user.answer_flag_idx then b.choice_3
                when 4 = awb_trn_quiz_user.answer_flag_idx then b.choice_4 end as user_answer'),
            DB::RAW('case when awb_trn_quiz_user.answer_result = 1 then "Correct" else "Wrong" end as quiz_result'),
            DB::RAW('(SELECT title FROM awb_trn_article d WHERE b.trn_article_id = d.id) as article_title')
            )
            ->leftJoin('awb_trn_quiz as b', 'b.id', '=', 'awb_trn_quiz_user.trn_quiz_id')
            ->join('users as c', 'c.id', '=', 'awb_trn_quiz_user.user_modified')
            ->when($this->filter_category == 'iqos', function($query) {
                $query->join('awb_trn_article as e', function($join) {
                    $join->on('e.id', '=', 'b.trn_article_id')
                        ->on('e.category_id','=', DB::raw($this->category_iqos_delivery));
                });
            })
            ->where('c.status_active', '=', 1)
            ->where('awb_trn_quiz_user.platform_id', '=', $this->platform_id)
            ->when(isset($this->filter_period_from, $this->filter_period_to),
                function ($query) {
                $query->whereBetween(DB::raw('convert(awb_trn_quiz_user.date_modified,date)'), [$this->filter_period_from, $this->filter_period_to]);
            });
    }
}
