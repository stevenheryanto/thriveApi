<?php

namespace App\Exports;

use App\Models\TTR\Activity_log;
use Illuminate\Support\Facades\DB;
use App\Models\TTR\Signature;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;


class EnergyExport implements Responsable, FromQuery, WithHeadings
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
        if($this->type == 'signature'){
            return [
                'Name',
                'Total',
                'Y'
            ];
        }else{
            return [
                'Name',
                'Signature',
                'Total',
                'Y',
                'Status',
                'Deleted'
            ];
        }
    }

    // public function map($result): array
    // {
    //     return [
    //         $result->name,
    //         $result->total,
    //         $result->y
    //     ];
    // }

    public function __construct($grand_total, $platform_id, $energy_point, $type, $startDate, $endDate)
    {
        $this->grand_total = $grand_total;
        $this->platform_id = $platform_id;
        $this->energy_point = $energy_point;
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        $subquery = Signature::query()
        ->select('signature.signature AS name',DB::Raw('IFNULL( SUM(user_vote.point_score) , 0 ) AS total'), DB::Raw('round( (IFNULL( SUM(user_vote.point_score) , 0 ) / '.$this->grand_total.') * '.$this->energy_point.') AS y'))
        ->leftJoin('behavior', 'behavior.signature' , '=' , 'signature.id')
        ->leftJoin('user_vote', 'user_vote.behavior_id' , '=' , 'behavior.id')
        ->where([
           ['signature.status_active', '=', "1"],
           ['signature.is_deleted', '=', '0'],
           ['behavior.status_active', '=', "1"],
           ['behavior.is_deleted', '=', '0'],
           ['signature.platform_id', '=', $this->platform_id]
        ])
        ->when(!empty($this->startDate) && !empty($this->endDate), function($query){
            $query->whereBetween(DB::raw('date(user_vote.date_created)'),[$this->startDate,$this->endDate]);
        })
        ->groupBy('name')
        ->orderBy('name');
        //->get();

        // $result = DB::table( DB::raw("(select a.signature as name, ifnull(sum(c.point_score),0) as total
        // from signature a left join
        //     behavior b on a.id = b.signature left join
        //     user_vote c ON b.id = c.behavior_id
        // where a.status_active = 1 and a.is_deleted = 0
        // and a.platform_id = 1
        // group by a.signature) as vw"))
        // ->select('vw.name','vw.total', DB::Raw('round((vw.total / 14000 ) * 100) as y'))->get();

        // $sql = "=
        // select name, total, round((total / 14300) * 100) as y
        //     from
        //     (
        //         select a.signature as name, ifnull(sum(c.point_score),0) as total
        //         from signature a left join
        //             behavior b on a.id = b.signature left join
        //             user_vote c ON b.id = c.behavior_id
        //         where a.status_active = 1 and a.is_deleted = 0
        //         and a.platform_id = 1
        //         group by a.signature
        //     ) as vw
        // ";


        $sql = "
            select name, signature,total, round((total / :grand_total) * 100) as y, status, deleted
                from
                (
                    select b.behavior as name, a.signtag as signature, ifnull(sum(c.point_score),0) as total, if(b.status_active = 0,'INACTIVE','ACTIVE') as status, if(b.is_deleted = 0,'NO','YES') as deleted
                    from signature a left join
                        behavior b on a.id = b.signature left join
                        user_vote c ON b.id = c.behavior_id
                    where a.status_active = 1 and a.is_deleted = 0
                    and a.platform_id = :platform_id
                    group by b.behavior
                ) as vw
        ";
        $subquery2 = Signature::query()
        ->select('behavior.behavior AS name','signature.signtag as signature',
        DB::Raw('IFNULL( SUM(user_vote.point_score) , 0 ) AS total'),
        DB::Raw('round( (IFNULL( SUM(user_vote.point_score) , 0 ) / '.$this->grand_total.') * '.$this->energy_point.') AS y'),
        DB::Raw('if(behavior.status_active = 0,"INACTIVE","ACTIVE") as status'),
        DB::Raw('if(behavior.is_deleted = 0,"NO","YES") as deleted'))
        ->leftJoin('behavior', 'behavior.signature' , '=' , 'signature.id')
        ->leftJoin('user_vote', 'user_vote.behavior_id' , '=' , 'behavior.id')
        ->where([
           ['signature.status_active', '=', "1"],
           ['signature.is_deleted', '=', '0'],
           ['behavior.status_active', '=', "1"],
           ['behavior.is_deleted', '=', '0'],
           ['signature.platform_id', '=', $this->platform_id]
        ])
        ->when(!empty($this->startDate) && !empty($this->endDate), function($query){
            $query->whereBetween(DB::raw('date(user_vote.date_created)'),[$this->startDate,$this->endDate]);
        })
        ->groupBy('name','signature','status','deleted')
        ->orderBy('name')
        ->orderBy('signature')
        ->orderBy('deleted')
        ->orderBy('status');
        if($this->type == 'signature'){
            return $subquery;
        }else{
            return $subquery2;
        }

    }
}
