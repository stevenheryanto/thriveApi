<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Exports\AWB\SubmittedIdeaExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SubmittedIdeaController extends Controller{

    public function ListData(Request $request)
	{	
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');

        try {
            $query = DB::table('awb_trn_submit_idea as a')->select(
                'a.id','b.id as user_id',
                'a.message_idea', 'a.date_created as submitted_date',
                'b.name as user_name','b.account as user_account','b.email as user_email',
                'b.directorate'
                )->join('users as b', 'b.id', '=', 'a.user_created')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($filter_period_from, $filter_period_to),
                    function ($query) use ($filter_period_from, $filter_period_to) {
                    $query->whereBetween(DB::raw('convert(a.date_created, date)'), [$filter_period_from, $filter_period_to]);
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


    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::cz_delete('awb_trn_submit_idea','md5(id)',$id);
            return response()->json([
                'data' => true,
                'message' => 'delete success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'delete failed: '.$th
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
            return new SubmittedIdeaExport($platform_id, $filter_period_from, $filter_period_to);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
