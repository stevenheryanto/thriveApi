<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller
{
    public function ListData(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $str_where = ' ';
        if(!empty($startDate) && !empty($endDate))
        {
            $str_where =  $str_where." and date(access_date) between :startDate and :endDate ";
        }
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $plat_name = DB_global::cz_select('SELECT name FROM recognize_platform_hdr WHERE id=:id', [$platform_id], 'name');
        $access_module = 'Recognition - '.$plat_name;

        $sql = "SELECT *
                FROM
                    activity_log
                WHERE
                    access_module = :access_module
                    $str_where
                ORDER BY id DESC ";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT :offset, :limit ";
            $param = array(
                'limit'=>$limit,
                'offset'=>$offset,
                'access_module'=>$access_module
                );
        }else{
            $param = array(
                'access_module'=>$access_module
            );
        }
        if(!empty($startDate) && !empty($endDate))
        {
            $param = array_merge($param, array(
                'startDate'=> $startDate,
                'endDate' => $endDate
            ));
        }
        try {
            $data = DB_global::cz_result_set($sql,$param,false,$category);
            return response()->json([
                'data' => $data,
                'data2' => $startDate,
                'data3' => $endDate,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function FormExport(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dateStamp =  date('Ymdhms');
        $plat_name = DB_global::cz_select('SELECT name FROM recognize_platform_hdr WHERE id=:id', [$platform_id], 'name');
        $access_module = 'Recognition - '.$plat_name;
        try {
            $folder_name = 'recognition/temp/';
            $excelName = 'activity_log_'.$dateStamp. '.xlsx';

            // Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $excelName);
            // $path = Storage::path($excelName);
            Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $folder_name.$excelName);
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            // return response()->download($path, $excelName, $headers)->deleteFileAfterSend(true);
            return Storage::get($path, 200, $headers);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function Export()
    {
        return Excel::download(new ActivityLogExport, 'act_log.xlsx');
    }

    public function CountActivityLogUser(Request $request){
        try {
            $sql = "SELECT count(*) as total FROM `activity_log` WHERE `user_id` = ? and `access_module` = ? and `access_feature` = ?";

            $data = DB_global::cz_result_array($sql,$request->all());
            //code...
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
