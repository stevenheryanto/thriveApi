<?php

namespace App\Exports;

use App\Models\TTR\User_post;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RawDataExport implements Responsable, FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    use Exportable;

    public function headings(): array
    {
        return [
            'ID',
            'Recipient Name',
            'Recipient Email',
            'Point',
            'Behaviour',
            'Signature',
            'Post',
            'Date',
            'Given By Name',
            'Given By Email',
            'On Behalf By Name',
            'On Behalf By Email',
            'Total Comments',
            'Total Likes',
            'Upload Image'
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->recipient_name,
            $result->recipient_email,
            $result->point_score,
            $result->behaviour,
            $result->signature,
            strip_tags($result->post_content),
            $result->date_created,
            $result->given_by_name,
            $result->given_by_email,
            $result->on_behalf_by_name,
            $result->on_behalf_by_email,
            $result->total_comment,
            $result->total_like,
            $result->upload_image,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'Z' => 'dd/mmm/yyyy h:mm:ss',
        ];
    }

    public function __construct($platform_id, $where, $year, $month)
    {
        $this->platform_id = $platform_id;
        $this->where = $where;
        $this->year = $year;
        $this->month = $month;
    }

    public function query()
    {

        return User_post::query()
            ->select('user_post.id'
            ,'user_vote.recipient_name'
            ,'user_vote.recipient_email'
            ,'user_vote.point_score'
            ,'behavior.hashtag AS behaviour'
            ,'signature.signtag AS signature'
            ,strip_tags('user_post.post_content')
            ,'user_post.date_created'
            ,'user_vote.given_by_name'
            ,'user_vote.given_by_email'
            ,DB::raw('ifnull(b1.total_comment, 0) AS total_comment')
            ,DB::raw('ifnull(c1.total_like, 0) AS total_like')
            ,'users.email AS on_behalf_by_email'
            ,'users.NAME AS on_behalf_by_name'
            ,'users.account AS user_login_name'
            ,'user_post.upload_image'
            ,'a.account AS sender_account'
            ,'b.account AS recipient_account'
            )
            ->leftJoin('user_vote', 'user_vote.user_post_id' , '=' , 'user_post.id')
            ->leftJoin('behavior', 'behavior.id' , '=' , 'user_vote.behavior_id')
            ->leftJoin('signature', 'signature.id' , '=' , 'behavior.signature')
            ->leftJoin('users', 'users.id' , '=' , 'user_post.user_onbehalf_by')
            ->join('users as a',DB::raw('a.id'),'=',DB::raw('user_post.user_created'))
            ->join('users as b',DB::raw('b.id'),'=',DB::raw('user_vote.user_id'))
            ->leftJoin(DB::raw('(select user_post_id, count(id) as total_comment from user_comment
            group by user_post_id) as b1'), 'user_post.id', '=', 'b1.user_post_id')
            ->leftJoin(DB::raw('(select user_post_id, count(id) as total_like from user_like
            group by user_post_id ) as c1'), 'user_post.id', '=', 'c1.user_post_id')
            ->where('user_post.platform_id', '=', $this->platform_id)
            ->whereNotNull('behavior.id')
            ->when(!is_null($this->month), function ($query) {
                $query->whereMonth('user_post.date_created', $this->month);
            })
            ->when(!is_null($this->year), function ($query) {
                $query->whereYear('user_post.date_created', $this->year);
            })
            ->when(!is_null($this->where), function ($query) {
                $query->where('user_post.id','like', '%'.$this->where.'%')
                ->orWhere('user_vote.recipient_email','like', '%'.$this->where.'%')
                ->orWhere('user_vote.recipient_name','like', '%'.$this->where.'%')
                ->orWhere('user_vote.given_by_email','like', '%'.$this->where.'%')
                ->orWhere('user_vote.given_by_name','like', '%'.$this->where.'%');
            });

    }
}
