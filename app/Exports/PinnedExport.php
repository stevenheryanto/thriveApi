<?php

namespace App\Exports;

use App\Models\TTR\User_post;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PinnedExport implements Responsable, FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Id',
            'Posted By',
            'Posted Date',
            'Point',
            'Post',
            'Flag Pinned'
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->name ,
            Date::dateTimeToExcel($result->date_created),
            $result->point_score,
            $result->post_content,
            $result->pinned_flag
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => 'dd/mmm/yyyy h:mm:ss',
        ];
    }

    public function __construct($platform_id, $tabtype, $where)
    {
        $this->platform_id = $platform_id;
        $this->tabtype = $tabtype;
        $this->where = $where;
    }

    public function query()
    {        
        return User_post::query()
            ->select('user_post.id','users.name', 'user_post.date_created','user_post.point_score','user_post.post_content','user_post.pinned_flag')
            ->leftJoin('users', 'users.id' , '=' , 'user_post.user_created')
            ->where('platform_id', '=', $this->platform_id)
            ->whereNotNull('users.id') 
            ->when(!is_null($this->where), function ($query) {
                $query->where('user_post.id','like', '%'.$this->where.'%')
                ->orWhere('users.name','like', '%'.$this->where.'%')
                ->orWhere('users.account','like', '%'.$this->where.'%');
            })
            ->when(($this->tabtype == 2), function ($query){
                $query->where('user_post.pinned_flag', '=', 1);
            });
    }
}
