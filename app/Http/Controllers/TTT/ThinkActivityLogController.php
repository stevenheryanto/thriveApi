<?php

namespace App\Http\Controllers\TTT;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ThinkActivityLogController extends Controller{
    protected $table_name = 'activity_log';

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
        $module_name = $request->input('module_name');
        $platform_id = $request->input('platform_id');

        if ($module_name == "Landing Page"){
            $plat_name = DB_global::cz_select('SELECT name FROM menu_platform_hdr WHERE id=:id', [$platform_id], 'name');
        } else if ($module_name == "Time to Think"){
            $plat_name = DB_global::cz_select('SELECT name FROM timetothink_platform_hdr WHERE id=:id', [$platform_id], 'name');
        } else if ($module_name == "Time to Listen"){
            $plat_name = DB_global::cz_select('SELECT name FROM dialogue_platform_hdr WHERE id=:id', [$platform_id], 'name');
        } else if ($module_name == "Find Time"){
            $plat_name = DB_global::cz_select('SELECT name FROM ideation_platform_hdr WHERE id=:id', [$platform_id], 'name');
        }

        $access_module = $module_name.' - '.$plat_name;

        $sql = "SELECT *
            FROM
                $this->table_name
            WHERE
               access_module = :access_module
               $str_where
            ORDER BY ID DESC ";

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
            //throw $th;
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
        $module_name = $request->input('module_name');
        if ($module_name == "Landing Page"){
            $plat_name = DB_global::cz_select('SELECT name FROM menu_platform_hdr WHERE id=:id', [$platform_id], 'name');
        } else if ($module_name == "Time to Think"){
            $plat_name = DB_global::cz_select('SELECT name FROM timetothink_platform_hdr WHERE id=:id', [$platform_id], 'name');
        } else if ($module_name == "Time to Listen"){
            $plat_name = DB_global::cz_select('SELECT name FROM dialogue_platform_hdr WHERE id=:id', [$platform_id], 'name');
        } else if ($module_name == "Find Time"){
            $plat_name = DB_global::cz_select('SELECT name FROM ideation_platform_hdr WHERE id=:id', [$platform_id], 'name');
        }

        $access_module = $module_name.' - '.$plat_name;
        try {
            $folder_name = $module_name.'/temp/';
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
}
