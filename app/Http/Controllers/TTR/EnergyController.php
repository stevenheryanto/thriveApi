<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exports\EnergyExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class EnergyController extends Controller
{
    function CountGrantTotal($platform_id, $startDate, $endDate)
    {
        $param = array(
            'platform_id'=> $platform_id
        );

        $str_where = ' ';
        if(!empty($startDate) && !empty($endDate))
        {
            $str_where =  $str_where." and date(c.date_created) between :startDate and :endDate ";
            $param = array_merge($param,['startDate'=>$startDate,'endDate'=>$endDate]);
        }

        $sql_grand_total = "
        select ifnull(sum(c.point_score),0) as grand_total
        from signature a
            left join behavior b on a.id = b.signature
            left join user_vote c ON b.id = c.behavior_id
        where a.status_active = 1
            and a.is_deleted = 0
            and b.status_active = 1
            and b.is_deleted = 0
            and a.platform_id = :platform_id $str_where";
        $grand_total = DB_global::cz_select($sql_grand_total, $param, "grand_total");

        return $grand_total;
    }

    public function EnergySignature(Request $request)
    {
        $userId = $request->input('userId');
        $platform_id = $request->input('platform_id');
        $energy_point = $request->input('energy_point');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $grand_total = $this->CountGrantTotal($platform_id, $startDate, $endDate);
        $energy_point = 100;

        $param = array(
            'grand_total'=> $grand_total,
            'platform_id'=> $platform_id
        );

        $str_where = ' ';
        if(!empty($startDate) && !empty($endDate))
        {
            $str_where =  $str_where." and date(c.date_created) between :startDate and :endDate ";
            $param = array_merge($param,['startDate'=>$startDate,'endDate'=>$endDate]);
        }

		$sql = "select a.signature as name,  round((ifnull(sum(c.point_score),0) / :grand_total) * $energy_point) as y
					from signature a left join
						 behavior b on a.id = b.signature left join
						 user_vote c ON b.id = c.behavior_id
                    where a.status_active = 1 and a.is_deleted = 0 and b.status_active = 1 and b.is_deleted = 0
                    and a.platform_id = :platform_id
                    $str_where
					group by a.signature";

        try {


            $data = DB_global::cz_result_set($sql,$param);

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function EnergyBehavior(Request $request)
    {
        $userId = $request->input('userId');
        $platform_id = $request->input('platform_id');
        $energy_point = $request->input('energy_point');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $grand_total = $this->CountGrantTotal($platform_id, $startDate, $endDate);

        $energy_point = 100;

        $param = array(
            'grand_total'=> $grand_total,
            'platform_id'=> $platform_id
        );

        $str_where = ' ';
        if(!empty($startDate) && !empty($endDate))
        {
            $str_where =  $str_where." and date(c.date_created) between :startDate and :endDate ";
            $param = array_merge($param,['startDate'=>$startDate,'endDate'=>$endDate]);
        }


		$sql = "select concat(name,' (',total,')') as name,round((total / :grand_total) * $energy_point) as y  from
			(
				select b.behavior as name, ifnull(sum(c.point_score),0) as total
                    from signature a
                        left join behavior b on a.id = b.signature
                        left join user_vote c ON b.id = c.behavior_id
					where
                        (a.status_active = 1 and a.is_deleted = 0 and b.status_active = 1 and b.is_deleted = 0)
                        and a.platform_id = :platform_id $str_where
					group by b.behavior
			) as vw
		";

        try {


            $data = DB_global::cz_result_set($sql,$param);

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	function EnergySignatureExport(Request $request)
    {
        $userId = $request->input('userId');
        $platform_id = $request->input('platform_id');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $grand_total = $this->CountGrantTotal($platform_id, $startDate, $endDate);
        $energy_point = $request->input('energy_point');
        $energy_point = 100;
        // $sql = "
        //     select name, total, round((total / :grand_total) * 100) as y
		// 		from
        //         (
        //             select a.signature as name, ifnull(sum(c.point_score),0) as total
        //             from signature a left join
        //                 behavior b on a.id = b.signature left join
        //                 user_vote c ON b.id = c.behavior_id
        //             where a.status_active = 1 and a.is_deleted = 0
        //             and a.platform_id = :platform_id
        //             group by a.signature
        //         ) as vw
		// ";

        try {
            // $param = array(
            //     'grand_total'=> $grand_total,
            //     'platform_id'=> $platform_id
            // );

            $data = "" ;//DB_global::cz_result_set($sql,$param);

            $dateStamp =  date('Ymdhms');
            $folder_name = 'recognition/temp/';
            $excelName = 'energy_signature_'.$dateStamp. '.xlsx';

            // Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $excelName);
            // $path = Storage::path($excelName);
            Excel::store(new EnergyExport($grand_total, $platform_id, $energy_point, 'signature', $startDate, $endDate), $folder_name.$excelName);
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            // return response()->download($path, $excelName, $headers)->deleteFileAfterSend(true);
            return Storage::get($path, 200, $headers);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function EnergyBehaviorExport(Request $request)
    {
        $userId = $request->input('userId');
        $platform_id = $request->input('platform_id');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $grand_total = $this->CountGrantTotal($platform_id, $startDate, $endDate);
        $energy_point = $request->input('energy_point');
        $energy_point = 100;
        // $sql = "
        //     select name, signature,total, round((total / :grand_total) * 100) as y, status, deleted
        //         from
        //         (
        //             select b.behavior as name, a.signtag as signature, ifnull(sum(c.point_score),0) as total, if(b.status_active = 0,'INACTIVE','ACTIVE') as status, if(b.is_deleted = 0,'NO','YES') as deleted
        //             from signature a left join
        //                 behavior b on a.id = b.signature left join
        //                 user_vote c ON b.id = c.behavior_id
        //             where a.status_active = 1 and a.is_deleted = 0
        //             and a.platform_id = :platform_id
        //             group by b.behavior
        //         ) as vw
        // ";

        try {
            // $param = array(
            //     'grand_total'=> $grand_total,
            //     'platform_id'=> $platform_id
            // );

            // $data = DB_global::cz_result_set($sql,$param);

            $dateStamp =  date('Ymdhms');
            $folder_name = 'recognition/temp/';
            $excelName = 'energy_behavior_'.$dateStamp. '.xlsx';

            // Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $excelName);
            // $path = Storage::path($excelName);
            Excel::store(new EnergyExport($grand_total, $platform_id, $energy_point, 'behavior', $startDate, $endDate), $folder_name.$excelName);
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            // return response()->download($path, $excelName, $headers)->deleteFileAfterSend(true);
            return Storage::get($path, 200, $headers);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
