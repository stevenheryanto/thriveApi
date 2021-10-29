<?php

namespace App\Http\Controllers\AWB;


use DB_global;
use App\Exports\AWB\RegisterCourseExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class RegisterCourseController extends Controller{

    public function ListData(Request $request)
	{	
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        try {

            $query = DB::table('awb_trn_course as t')->select(
                    DB::RAW('ifnull(u.name,u.full_name) as user_name'), 'u.id', 'm.title'
                    , 'u.directorate as group_function'
                    , DB::RAW("(SELECT group_grade  
                        FROM awb_users_info 
                        WHERE id = t.user_created) as group_grade")
                )
                ->leftJoin('awb_mst_course as m', 'm.id', '=', 't.course_id')
                ->join('users as u', 'u.id', '=', 't.user_created')
                ->where('t.platform_id', '=', $platform_id)
                ->when(isset($filter_period_from, $filter_period_to),
                    function ($query) use ($filter_period_from, $filter_period_to) {
                    $query->whereBetween(DB::raw('convert(t.date_created,date)'), [$filter_period_from, $filter_period_to]);
                });
                    
            if($category != "COUNT"){
                $data = $query->when(isset($offset, $limit), function($query) use($offset, $limit){
                        $query->offset($offset)->limit($limit);
                    })->orderBy('u.id', 'desc')
                    ->get();
            } else {
                $data = $query->count();
            }
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

    public function ExportData(Request $request)
	{	
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        try {
            ini_set('memory_limit','512M');
            return new RegisterCourseExport($platform_id, $filter_period_from, $filter_period_to);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
