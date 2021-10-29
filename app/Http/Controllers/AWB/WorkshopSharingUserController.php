<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkshopSharingUserController extends Controller
{
    public function ListData(Request $request)
	{
        // $where          = $request->input('str_where');
        $limit          = $request->input('limit');
        $offset         = $request->input('offset');
        $category       = $request->input('category');
        // $categoryId     = $request->input('categoryId');
        $workshopId     = $request->input('workshopId');
        // $export         = $request->input('export');
        $platform_id    = $request->input('platform_id');
        
        try {
            $query = DB::table('awb_trn_workshop_sharing_user as a')->select('a.*',
                'b.account',
                'b.email',
                'b.name'
                )->join('users as b', 'a.user_id', '=', 'b.id')
                ->where('a.platform_id', '=', $platform_id)
                ;

            if(isset($workshopId)){
                $query = $query->where(DB::RAW('md5(a.awb_trn_workshop_sharing_id)'), '=', $workshopId);
            }           

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('b.name')
                    ->get();
            } else {
                $data = $query->count();
            }

            return response()->json([
                'data'      => $data,
                'message'   => 'success',
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function selectDataByUserAndWorkshopId(Request $request){
        $userId = $request->input('userId');
        $workshopId = $request->input('workshopId');
        try {
            //code...
            $sql = "select * from awb_trn_workshop_sharing_user where user_id = ? and awb_trn_workshop_sharing_id = ? limit 1";

            $data = DB_global::cz_result_array($sql,[$userId,$workshopId]);

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cekCountUserInWorkshopSharing(Request $request){
        $workshopId = $request->input('workshopId');
        try {
            //code...
            $sql = "select
                count(awb_trn_workshop_sharing_user.awb_trn_workshop_sharing_id)  as total_user
            from
                awb_trn_workshop_sharing_user
            where
                awb_trn_workshop_sharing_user.awb_trn_workshop_sharing_id = ? ";

            $data = DB_global::cz_result_array($sql,[$workshopId]);

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function InsertData(Request $request){
        $_arrayData = $request->input();

        try {
            //code...
            $_arrayData = array_merge($_arrayData,
            array(
                'date_created'=> DB_global::Global_CurrentDatetime(),
            ));

            DB_global::cz_insert('awb_trn_workshop_sharing_user',$_arrayData);

            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
