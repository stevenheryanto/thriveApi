<?php

namespace App\Exports;

use DB_global;
use App\Models\TTR\User;
use App\Models\TTR\recognize_platform_dtl_3;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RawDataExport2 implements Responsable, FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Name',
            'IMDL ID',
            'Windows Account',
            'Title',
            'Business Unit',
            'Directorate',
            'Division-P',
            'Division-Q',
            'Department',
            'Receiver',
            'Contributor'
        ];
    }

    public function map($result): array
    {
        return [
            $result->name,
            $result->id,
            $result->account,
            $result->title,
            $result->business_unit,
            $result->directorate,
            $result->division_p,
            $result->division_q,
            $result->department,
            $result->receivers,
            $result->contributor
        ];
    }

    public function columnFormats(): array
    {
        return [
            'Z' => 'dd/mmm/yyyy h:mm:ss',
        ];
    }

    public function __construct($platform_id, $where)
    {
        $this->platform_id = $platform_id;
        $this->where = $where;
    }

    public function query()
    {        
        
        $chkCountry = DB_global::cz_result_set('SELECT country FROM recognize_platform_dtl_1 WHERE platform_id=:platform_id', [$this->platform_id]);
        $chkDirectorate = DB_global::cz_result_set('SELECT directorate FROM recognize_platform_dtl_2 WHERE platform_id=:platform_id', [$this->platform_id]);
        $adhoc_user = recognize_platform_dtl_3::select('imdl_id')->from('recognize_platform_dtl_3')
        ->where([
            ['platform_id','=',$this->platform_id],
            ['flag_active','=',1]
        ]);
        $arrCountry = [];
        if(count($chkCountry) > 0){
            foreach($chkCountry as $countryDtl){
                $arrCountry = array_merge($arrCountry, [$countryDtl->country]);
            }
        }

        $arrDirectorate = [];
        if(count($chkDirectorate) > 0){
            foreach($chkDirectorate as $directorateDtl){
                $arrDirectorate = array_merge($arrDirectorate, [$directorateDtl->directorate]);
            }
        }

        return User::query()
            ->select('users.id','users.name','users.account','users.business_unit','users.title','users.business_unit'
                ,'users.directorate','users.division_p', 'users.division_q','users.department'
                ,'z.receivers','y.contributor'
            )
            ->leftJoin(DB::raw('(
                select a.user_created as id, sum(a.point_score) as contributor
                from 	
                    user_vote a
                group by a.user_created
            ) as y'), 'users.id', '=', 'y.id')
            ->leftJoin(DB::raw('(
                select a.user_id as id, sum(a.point_score) as receivers
					from 	
						user_vote a 
					group by a.user_id
            ) as z'), 'users.id', '=', 'z.id')
            ->where(function ($query) {
                $query->where('z.receivers', '>', 0)
                    ->orWhere('y.contributor', '>', 0);
            })
            // ->whereIn('users.directorate', $arrDirectorate)
            // ->whereIn('users.country', $arrCountry)
            ->when(count($arrDirectorate)>0, function ($query) use ($arrDirectorate) {
                $query->whereIn('users.directorate', $arrDirectorate);
            })
            ->when(count($arrCountry)>0, function ($query) use ($arrCountry){
                $query->whereIn('users.country', $arrCountry);
            })
            ->orWhereIn('users.id', $adhoc_user)
            ->when(!is_null($this->where), function ($query) {
                $query->where('users.id','like', '%'.$this->where.'%')
                ->orWhere('users.name','like', '%'.$this->where.'%')
                ->orWhere('users.account','like', '%'.$this->where.'%')
                ->orWhere('users.email','like', '%'.$this->where.'%');
            })
            ->orderBy('z.receivers', 'desc')
            ->orderBy('y.contributor', 'desc');
    }
}
