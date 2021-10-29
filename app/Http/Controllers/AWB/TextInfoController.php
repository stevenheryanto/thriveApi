<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_trn_text_info;

class TextInfoController extends Controller
{
    protected $table_name = 'awb_trn_text_info';



    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');

        $sql = "
            select
                a.*,b.name as fullname, c.name as exam_name
            from
                " .  $this->table_name ." a left join
                users b on a.user_modified = b.id left join sw_mst_exam c on a.exam_id = c.id
            where
                a.platform_id = :platform_id
            order by
                a.text_info

            ";

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


    public function UpdateData(Request $request)
    {
        $id = $request->input('_code');
        $all = $request->except('id','user_account');

        //var_dump($all);exit();
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name,'_code', $id, $all);
            return response()->json([
                'data' => $data,
                'message' => 'data update success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data update failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function SubmitDataCategory(Request $request)
    {
        $id = $request->input('awb_trn_category_id');

        $sql     = "select * from $this->table_name where awb_trn_category_id =  $id limit 1";
        $dataCek = DB_global::cz_result_array($sql);

        if($dataCek){
            $all = $request->except('id','user_account');
            $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
            $all = array_merge($all, ['user_modified' => $request->input('user_modified')]);
            try {
                $data = DB_global::cz_update($this->table_name,'awb_trn_category_id', $id, $all);
                return response()->json([
                    'data' => $data,
                    'message' => 'data update success'
                ]);
            } catch (\Throwable $th) {
                return response()->json([
                    'data' => false,
                    'message' => 'data update failed: '.$th
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }


        }else{
            $_arrayData = array(
                'platform_id'           =>$request->input('platform_id'),
                'awb_trn_category_id'   =>$request->input('awb_trn_category_id'),
                'text_info'             =>$request->input('text_info'),
                'user_modified'          =>$request->input('user_modified'),
                'date_modified'          => DB_global::Global_CurrentDatetime(),
            );

            try {
                $data = DB_global::cz_insert($this->table_name, $_arrayData, false);
                return response()->json([
                    'data' => true,
                    'message' => 'data insert success'
                ]);
            } catch (\Throwable $th) {
                return response()->json([
                    'data' => false,
                    'message' => 'data update failed: '.$th
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }



    }

    public function SubmitDataMenu(Request $request)
    {
        $id = $request->input('awb_mst_menu_id');


        $sql     = "select * from $this->table_name where awb_mst_menu_id =  $id limit 1";
        $dataCek = DB_global::cz_result_array($sql);

        if($dataCek){
            $all = $request->except('id','user_account');
            $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
            $all = array_merge($all, ['user_modified' => $request->input('user_modified')]);
            try {
                $data = DB_global::cz_update($this->table_name,'awb_mst_menu_id', $id, $all);
                return response()->json([
                    'data' => $data,
                    'message' => 'data update success'
                ]);
            } catch (\Throwable $th) {
                return response()->json([
                    'data' => false,
                    'message' => 'data update failed: '.$th
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }


        }else{
            $_arrayData = array(
                'platform_id'           =>$request->input('platform_id'),
                'awb_mst_menu_id'   =>$request->input('awb_mst_menu_id'),
                'text_info'             =>$request->input('text_info'),
                'user_modified'          =>$request->input('user_modified'),
                'date_modified'          => DB_global::Global_CurrentDatetime(),
            );

            try {
                $data = DB_global::cz_insert($this->table_name, $_arrayData, false);
                return response()->json([
                    'data' => true,
                    'message' => 'data insert success'
                ]);
            } catch (\Throwable $th) {
                return response()->json([
                    'data' => false,
                    'message' => 'data update failed: '.$th
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }



    }
    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::bool_ValidateDataOnTableById_md5($this->table_name, $id);
            return response()->json([
                'data' => true,
                'message' => 'validate success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'validate failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function SelectData(Request $request)
	{
        $id = $request->input('md5ID');
		$sql = "select * from $this->table_name where md5(awb_trn_category_id) = ? limit 1";

        try {
            $data = DB_global::cz_result_array($sql, [$id]);

            return response()->json([
                'data' => $data,
                'message' => 'select data success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'select data failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function SelectDataByMenu(Request $request)
	{
        $platform_id         = $request->input('platform_id');
        $menuId   = $request->input('menuId');
        $categoryId   = $request->input('categoryId');

        try {

            if(isset($categoryId)){
                $sql = "select * from $this->table_name where md5(awb_trn_category_id) = ? and platform_id = ? limit 1";
                $data = DB_global::cz_result_array($sql, [$categoryId,$platform_id]);
            }else{
                $sql = "select * from $this->table_name where md5(awb_mst_menu_id) = ? and platform_id = ? limit 1";
                $data = DB_global::cz_result_array($sql, [$menuId,$platform_id]);
            }

            return response()->json([
                'data' => $data,
                'message' => 'select data success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'select data failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::cz_delete($this->table_name,'_code',$id);
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
}
