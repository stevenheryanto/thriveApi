<?php

namespace App\Http\Controllers\AWB;

use App\Exports\AWB\ActivityLogExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller{

    public function ListData(Request $request)
	{
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        try {
            $query = DB::table('awb_trn_log as a')->select(
                'a.*','b.title as article_title'
                , DB::RAW("(SELECT title  
                    FROM awb_trn_category 
                    WHERE id = b.category_id) as category_title")
                )
                ->leftJoin('awb_trn_article as b', 'b.article_id', '=', 'a.transaction_id')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($filter_period_from, $filter_period_to), function ($query) use ($filter_period_from, $filter_period_to) {
                    $query->whereBetween(DB::raw('convert(a.log_date, date)'), [$filter_period_from, $filter_period_to]);
                });

            if($category != "COUNT"){
                $data = $query->when(isset($offset, $limit), function($query) use($offset, $limit){
                        $query->offset($offset)->limit($limit);
                    })->orderBy('a.id', 'desc')
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
            return new ActivityLogExport($platform_id, $filter_period_from, $filter_period_to);
        } catch (\Throwable $th) {
            Log::info('fail: ' .$th);
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
