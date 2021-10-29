<?php

namespace App\Http\Controllers\AWB;


use DB_global;
use App\Exports\AWB\EmailSubscribeExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EmailSubscribeController extends Controller{

    public function ListData(Request $request)
	{	
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        try {
            $query = DB::table('awb_trn_email_subscription as a')->select(
                    'a.id', 'b.id as user_id', 'a.date_subscription',
                    'b.name as user_name', 'b.account as user_account', 'b.email as user_email',
                    'b.directorate as user_function'
                )
                ->join('users as b', 'b.id', '=', 'a.id')
                ->where('a.flag_subscription','=','1')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($filter_period_from, $filter_period_to),
                    function ($query) use ($filter_period_from, $filter_period_to) {
                    $query->whereBetween(DB::raw('convert(a.date_subscription,date)'), [$filter_period_from, $filter_period_to]);
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
        $all = array(
            'date_subscription'=> DB_global::Global_CurrentDatetime(),
            'flag_subscription' => '0'
        );
        try {
            $data = DB_global::cz_update('awb_trn_email_subscription','md5(id)',$id, $all);
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

    public function Unsubscribe(Request $request)
	{
        $id = $request->input('id');
        $reason = $request->input('reason');
        $all = array(
            'date_subscription'=> DB_global::Global_CurrentDatetime(),
            'unsubscribe_reason'=> $reason,
            'flag_subscription' => '0'
        );
        try {
            $data = DB_global::cz_update('awb_trn_email_subscription','md5(id)',$id, $all);
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
            return new EmailSubscribeExport($platform_id, $filter_period_from, $filter_period_to);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
