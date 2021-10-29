<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BehaviorController extends Controller
{
    public function RsSignature(Request $request)
	{
        $platform_id = $request->input('platform_id');
		$sql = "select id,signtag,signtag_ind,signature,signature_ind
				from signature where is_deleted=0 and platform_id=? order by signtag";
        try {

            $data = DB_global::cz_result_set($sql,[$platform_id]);

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
    public function RsBehaviorListBySignatureId(Request $request)
	{
        $signature = $request->input('signature');
        $platform_id = $request->input('platform_id');
		$sql = "select a.id, a.hashtag, a.hashtag_ind, behavior, behavior_ind
                    from behavior a
                where a.is_deleted = 0 and a.status_active = 1 and a.signature = :signature and platform_id = :platform_id";
        try {
            $param = array(
                'signature'=>$signature,
                'platform_id'=>$platform_id
                );
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

    public function RsListBehaviorSignature(Request $request){
        $platform_id = $request->input('platform_id');
		$sql = "select b.signtag as signature , a.hashtag as behavior
                    from behavior a left join signature b on a.signature = b.id and a.platform_id = b.platform_id
                where a.is_deleted = 0 and b.is_deleted = 0 and a.status_active = 1 and b.status_active = 1 and a.platform_id = ?
                order by a.signature";
        try {

            $data = DB_global::cz_result_set($sql,[$platform_id]);

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

    public function ListData(Request $request)
    {

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');

        $sql = "select a.*,b.signtag,b.signtag_ind
            from behavior a left join
                signature b on a.signature = b.id
            where a.is_deleted = 0
                and b.is_deleted = 0
                and a.platform_id = :platform_id
            order by b.signtag";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT  :offset,:limit ";
            //code...
            $param = array(
             'limit'=>$limit,
             'offset'=>$offset,
             'platform_id'=>$platform_id
             );
        }else{
             //code...
             $param = array(
                 'platform_id'=>$platform_id
             );
        }

        try {

            $data = DB_global::cz_result_set($sql,$param,false,$category);

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

    public function SelectData(Request $request)
	{
        $id = $request->input('md5ID');

		$sql = "select * from behavior where md5(id) = :id  limit 1";
		#print $sql;
        try {
            $param = array(
                'id'=>$id,
            );

            $data = DB_global::cz_result_array($sql, $param);

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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();

        try {
            $_arrayData = array_merge($_arrayData,
            array(
                'date_created'=> DB_global::Global_CurrentDatetime(),
                'date_modified'=> DB_global::Global_CurrentDatetime()
            ));

            $data = DB_global::cz_insert('behavior',$_arrayData,false);
            return response()->json([
                'data' => true,
                'message' => 'data insert success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function UpdateData(Request $request)
    {
        $id = $request->input('id');
        $all = $request->except('id');
        try {
            $all = array_merge($all,
            array(
                'date_modified'=> DB_global::Global_CurrentDatetime()
            ));
            $data = DB_global::cz_update('behavior','id',$id,$all);

            return response()->json([
                'data' => true,
                'message' => 'data update success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data update failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::bool_ValidateDataOnTableById_md5('behavior',$id);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function CheckHashtag(Request $request)
    {
        $hashtag = $request->input('hashtag');
        $platform_id = $request->input('platform_id');

        $sql = "select a.*
            from user_vote a left join
            behavior c on a.behavior_id = c.id
            where c.hashtag = :hashtag
            and c.platform_id = :platform_id ";

        try {
            $param = array(
                'hashtag'=>$hashtag,
                'platform_id'=>$platform_id,
            );

            $data = DB_global::bool_CheckRowExist($sql, $param);
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

    public function ValidateHashtag(Request $request)
    {
        $hashtag = $request->input('hashtag');
        $platform_id = $request->input('platform_id');
        $sql = "select * from behavior where hashtag like :hashtag";

        $param =array(
            'hashtag'=> '%'.$hashtag.'%'
        );

        try {
            $data = DB_global::bool_CheckRowExist($sql, $param);
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
}
