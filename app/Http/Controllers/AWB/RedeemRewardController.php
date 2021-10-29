<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Exports\AWB\RedeemRewardExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class RedeemRewardController extends Controller{

    public function ListData(Request $request)
	{	
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');

        $platform_id = $request->input('platform_id');

        try {
            $query = DB::table('awb_trn_reward_claim as a')->select(
                'a.id', 'b.id as user_id', 'd.claim_point', 'd.title as reward', 'a.claim_date',
		 		'b.name as user_name', 'b.account as user_account', 'b.email as user_email',
				'b.directorate as user_function'
                )
                ->join('users as b', 'b.id', '=', 'a.user_created')
                ->leftJoin('awb_trn_reward as d', 'd.id', '=', 'a.reward_id')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($filter_period_from, $filter_period_to),
                    function ($query) use ($filter_period_from, $filter_period_to) {
                    $query->whereBetween(DB::raw('convert(a.claim_date,date)'), [$filter_period_from, $filter_period_to]);
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
            return new RedeemRewardExport($platform_id, $filter_period_from, $filter_period_to);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
