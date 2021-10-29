<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use AwbGlobal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LinkSourceController extends Controller
{
    protected $table_name = 'recognize_mst_link_source';
    protected $table_name_menu = 'menu_mst_link_source';
    protected $table_name_awb = 'awb_mst_link_source';

    public function ListData(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $module_name = $request->input('module_name');

        $platform_id = $request->input('platform_id');

        if($module_name == "menu"){
            $sql = "select a.*, md5(code) as 'md5code' from $this->table_name_menu a
            where a.platform_id = :platform_id
            order by a.source_name";
        }else{
            $sql = "select a.*, md5(code) as 'md5code' from $this->table_name a
            where a.platform_id = :platform_id
            order by a.source_name";
        }


        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT :offset, :limit  ";

            $param = array(
                'platform_id' => $platform_id,
                'limit' => $limit,
                'offset' => $offset
            );
        } else {
            $param = array(
                'platform_id' => $platform_id
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
        $module_name = $request->input('module_name');

        if($module_name == "menu"){
            $sql = "select * from $this->table_name_menu where md5(id) = ? limit 1";
        }else{
            $sql = "select * from $this->table_name where md5(id) = ? limit 1";
        }


        try {
            $data = DB_global::cz_result_array($sql,[$id]);
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
        $module_name = $request->input('module_name');
        $_arrayData = $request->except('user_account', 'module_name');
        $_arrayData = array_merge($_arrayData,[
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ]);
        try {
            if($module_name == "menu"){
                $data = DB_global::cz_insert($this->table_name_menu,$_arrayData,false);
            }
            else{
                $data = DB_global::cz_insert($this->table_name,$_arrayData,false);
            }

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
        $module_name = $request->input('module_name');
        $all = $request->except('id','user_account', 'module_name');
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            if($module_name == "menu"){
                $data = DB_global::cz_update($this->table_name_menu,'id',$id,$all);
            }else{
                $data = DB_global::cz_update($this->table_name,'id',$id,$all);
            }

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

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        $module_name = $request->input('module_name');
        try {
            if($module_name == "menu"){
                $data = DB_global::cz_delete($this->table_name_menu,'id',$id);
            }else{
                $data = DB_global::cz_delete($this->table_name,'id',$id);
            }

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

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        $module_name = $request->input('module_name');
        try {
            if($module_name == "menu"){
                $data = DB_global::bool_ValidateDataOnTableById_md5($this->table_name_menu,$id);
            }else{
                $data = DB_global::bool_ValidateDataOnTableById_md5($this->table_name,$id);
            }
            return response()->json([
                'data' => true,
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

    public function CheckUserAccess(Request $request)
    {
        $userName = $request->input("userName");
        $userId = $request->input("userId");
        $userAccount = $request->input("userAccount");
        $userEmail = $request->input("userEmail");
        $isMobile = $request->input('isMobile');
        $token = $request->input('token');
        $module_name = $request->input('module_name');

        $userData = AwbGlobal::getUserData($request->bearerToken());

        $global = new DB_global;

        $awbGlobal = new AwbGlobal;

        // if($module_name == "menu"){
        //     $sql = "select * from $this->table_name_menu where md5(code) = ? ";
        // }elseif ($module_name == "awb") {
        //     $sql = "select * from $this->table_name_awb where md5(code) = ? ";
        // }else{
        //     $sql = "select * from $this->table_name where md5(code) = ? ";
        // }
        try {

            switch ($module_name) {
                case 'menu':
                    # code...
                    $table = $this->table_name_menu;
                    $table_platform = 'menu_platform_hdr';
                    $module = 'Landing page - ';
                    $feature = 'Login from site : ';

                    break;
                case 'awb':
                        # code...
                    $table = $this->table_name_awb;
                    $table_platform = 'awb_platform_hdr';
                    $module = 'Learn - ';
                    $feature = 'Login from site : ';
                    break;
                default:
                    # code...
                    $table = $this->table_name;
                    $table_platform = 'recognize_platform_hdr';
                    $module = 'Recognition - ';
                    $feature = 'Login from site : ';
                    break;
            }

            $sql = "select * from $table where md5(code) = ? ";

            $data = DB_global::cz_result_array($sql,[$token]);

            if(count($data)>0){
                $_arrayData = array('total_hits' => $data['total_hits']+1);

                $param = ['id' => $data['platform_id']];

                $data2 = DB_global::cz_result_array("select * from $table_platform where id = :id", $param);

                DB_global::cz_update($table, 'id', $data['id'], $_arrayData);

                $moduleFull = $module.$data2['name'];

                $featureFull = $feature.$data['source_name'];

                if($module_name=='awb'){
                    $awbGlobal->GenerateLog($userData, $data['platform_id'], $featureFull, 'User Login', $isMobile);
                }else{
                    $global->GenerateLog($userName,$userId,$userAccount,$userEmail,$isMobile, $moduleFull, $featureFull);
                }
            }else{
                $data2=[];
            }


            return response()->json([
                'data' => $data,
                'data2' => $data2,
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
