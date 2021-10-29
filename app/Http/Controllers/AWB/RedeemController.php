<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use AwbGlobal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\TTT\User;
use App\Models\AWB\awb_trn_reward;
use App\Models\AWB\awb_mst_level;
use App\Models\AWB\awb_mst_page;
use App\Models\AWB\awb_trn_reward_claim;
use Illuminate\Support\Facades\DB;

class RedeemController extends Controller
{
    public function ListReward(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $directorate = $request->input('directorate');
        try {
            $subFirst = awb_trn_reward_claim::select(
                'reward_id',
                DB::RAW('COUNT(id) AS total_qty_claim')
                )
                ->where('platform_id', '=', $platform_id)
                ->groupBy('reward_id');
            $subSecond = awb_trn_reward_claim::select(
                'reward_id', 'user_id',
                DB::RAW('COUNT(reward_id) AS user_claim')
                )
                ->where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->groupBy('reward_id','user_id');
            $query = DB::table('awb_trn_reward as a')->select('a.*', 'b.total_qty_claim', 'c.user_claim',
                DB::raw("(a.qty_stock - ifnull(b.total_qty_claim,0)) as qty_available")
                )->leftJoinSub($subFirst, 'b', function($join){
                    $join->on('b.reward_id','=','a.id');
                })->leftJoinSub($subSecond, 'c', function($join){
                    $join->on('c.reward_id','=','a.id');
                })
                ->where('a.flag_active', '=', 1)
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($directorate), function($query) use($directorate){
                    $query->whereIn('directorate', ['All', $directorate]);
                }, function($query) {
                    $query->where('directorate', '=', 'All');
                })
                ->orderBy('claim_point', 'asc');
            $data = $query->get();
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

    public function ListRewardFaq(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $data = awb_mst_page::select('*')
                ->where('platform_id', '=', $platform_id)
                ->whereIn('id', [4, 5, 6])
                ->get();
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

	public function ListUserLevel(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $data = awb_mst_level::select('*')
                ->where('platform_id', '=', $platform_id)
                ->orderBy('id', 'asc')
                ->get();
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
	
	public function ClaimReward(Request $request)
	{
        $_arrayData = $request->input();
        //$all = $request->except('user_account','id');
        $platform_id = $request->input('platform_id');
        $productId = $request->input('productId');
        $user_id = $request->input('user_id');
        try {
            $reward = awb_trn_reward::where(DB::raw('md5(id)'), '=', $productId)->first();
            unset($_arrayData['productId']);
            $arrayRewardClaim = array_merge($_arrayData,
                array(
                    'reward_id' => $reward->id,
                    '_status_data' => 'Submitted',
                    'date_created' => DB_global::Global_CurrentDatetime(),
                    'date_modified' => DB_global::Global_CurrentDatetime(),
                    'claim_date' => DB_global::Global_CurrentDatetime(),
                    'user_created' => $user_id,
            ));
            $insertRewardClaim = DB_global::cz_insert('awb_trn_reward_claim', $arrayRewardClaim, false);

            $arrayPointHistory = array_merge($_arrayData,
                array(
                    'point' => -($reward->claim_point),
                    'source' => 'claim reward : ' . $reward->title,
                    'status_date' => DB_global::Global_CurrentDatetime(),
                    'user_modified' => $user_id,
                    'date_modified' => DB_global::Global_CurrentDatetime(),
            ));
            $insertPointHistory = DB_global::cz_insert('awb_trn_point_history',$arrayPointHistory, false);

            /* belum selesai */
            $data = AwbGlobal::UpdateUserPointAndLevel($user_id);

            return response()->json([
                'data' => $data,
                'message' => 'claim success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'claim failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}
}
