<?php

namespace App\Http\Controllers\TTL;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB_global;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ThemeController extends Controller
{
    protected $folder_name = 'listen/theme';
    protected $table = 'dialogue_theme';

    public function ListData(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');

        $sql = "select a.* from $this->table a left join dialogue_platform_hdr b on a.platform_id = b.id where b.status_active = 1 and b.id = :platform_id and a.is_deleted is null order by a.date_modified desc";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT  :offset,:limit ";
            $param = array(
             'limit'=>$limit,
             'offset'=>$offset,
             'platform_id'=>$platform_id
             );
        }else{
             $param = array(
                 'platform_id'=>$platform_id
             );
        }

        try {
            if ($category == "COLUMNS"){
                // $data = DB_global::cz_getTableColumns($this->table);
                $sql2 = "show full columns from $this->table";

                try {
                    $param2 = "'%'";
                    $data = DB_global::cz_result_set($sql2,[],false,"");
                    return response()->json([
                        'data' => $data,
                        'message' => 'success'
                    ]);

                } catch (\Throwable $th) {
                    //throw $th;
                    return response()->json([
                        'data' => false,
                        'message' => 'failed: '.$th
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }else{
                $data = DB_global::cz_result_set($sql,$param,false,$category);
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

    public function SelectData(Request $request){
        $id = $request->input('md5ID');
        $sql = "select * from $this->table where md5(id) = :id  limit 1";

        try {
                $data = DB_global::cz_result_array($sql,[$id]);
                $columnTable = DB_global::cz_getTableColumns($this->table);
                return response()->json([
                    'data' => $data,
                    'data2' => $columnTable,
                    'message' => 'success'
                ]);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'data2' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function SelectDataByPlatform(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $listTheme = $request->input('listTheme');
        $lang = $request->input('lang');
        $sql = "select * from $this->table where platform_id = :platform_id and status_active = 1 and is_deleted is null ";
        $param = ['platform_id' => $platform_id];

        try {
            if(isset($lang)){
                /* for language selection */
                $sql = $sql . " and lang = :lang";
                $param = array_merge($param,['lang' => $lang]);
            } else {
                if(!isset($listTheme)){
                    /* for modal platform selection */
                    /* get default theme is lang is not selected */
                    $sql = $sql . " and default_flag = 1 ";
                }
            }

            if(!isset($listTheme)){
                /* for modal platform selection */
                /* get default theme is lang is not selected */
                $data = DB_global::cz_result_array($sql, $param);
            } else {
                $data = DB_global::cz_result_set($sql, $param);
            }

            $columnTable = DB_global::cz_getTableColumns($this->table);
            return response()->json([
                'data' => $data,
                'data2' => $columnTable,
                'message' => 'success'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'data2' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function InsertData(Request $request)
    {
        // $arrFieldImageFile = array(
        //     "img_navbar_menu","img_home_menu",
        //     "img_report_menu","img_admin_menu",
        //     "img_burger_menu","img_email_menu",
        //     "img_close_popup","img_background_popup_modal",
        //     "img_home_menu_dialog","img_home_menu_yawa",
        //     "img_home_menu_gallery","img_home_menu_uft",
        //     "img_submit_dialogue_initiate","img_submit_yawa",
        //     "img_email_logo"
        // );
        // print_r($arrFieldImageFile);
        
        $sqlColumnImg = "SHOW columns FROM dialogue_theme WHERE field like 'img_%' ";
        $dataColumnName = DB_global::cz_result_set($sqlColumnImg, [], false, "");
        $arrFieldImageFile = array_column($dataColumnName, 'Field');
        // print_r($arrFieldImageFile);

        $_arrayData = $request->except('user_account');
        $x = 0;
        try{
            while($x < count($arrFieldImageFile)){
                if($request->hasFile($arrFieldImageFile[$x]))
                {
                    $file = $request->file($arrFieldImageFile[$x]);
                    $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
                    $fileName = DB_global::cleanFileName($fileName);
                    Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

                    unset($_arrayData[$arrFieldImageFile[$x]]);
                    $_arrayData = array_merge($_arrayData, [$arrFieldImageFile[$x] => $fileName]);
                }
                $x++;
            }
            $_arrayData = array_merge($_arrayData,
                array(
                    'date_modified'=> DB_global::Global_CurrentDatetime(),
                    'default_flag' => 0,
                    'is_deleted' => null
            ));

            $data = DB_global::cz_insert($this->table,$_arrayData,true);
            return response()->json([
                'data' => true,
                'message' => 'data insert success'
            ]);
        }
        catch (\Throwable $th){
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function  UpdateData(Request $request)
    {
        $id = $request->input('id');
        $_arrayData = $request->except('id','user_account');
        // $arrFieldImageFile = array(
        //     "img_navbar_menu","img_home_menu",
        //     "img_report_menu","img_admin_menu",
        //     "img_burger_menu","img_email_menu",
        //     "img_close_popup","img_background_popup_modal",
        //     "img_home_menu_dialog","img_home_menu_yawa",
        //     "img_home_menu_gallery","img_home_menu_uft",
        //     "img_submit_dialogue_initiate","img_submit_yawa",
        //     "img_email_logo"
        // );
        $sqlColumnImg = "SHOW columns FROM dialogue_theme WHERE field like 'img_%' ";
        $dataColumnName = DB_global::cz_result_set($sqlColumnImg, [], false, "");
        $arrFieldImageFile = array_column($dataColumnName, 'Field');
        // print_r($arrFieldImageFile);

        $x = 0;
        try{
            while($x < count($arrFieldImageFile)){
                if($request->hasFile($arrFieldImageFile[$x]))
                {
                    $file = $request->file($arrFieldImageFile[$x]);
                    $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
                    $fileName = DB_global::cleanFileName($fileName);
                    Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

                    unset($_arrayData[$arrFieldImageFile[$x]]);
                    $_arrayData = array_merge($_arrayData, [$arrFieldImageFile[$x] => $fileName]);
                }
                $x++;
            }
            $_arrayData = array_merge($_arrayData,
                array(
                    'date_modified'=> DB_global::Global_CurrentDatetime(),
                    'is_deleted' => null
            ));

            $data = DB_global::cz_update($this->table,'id',$id,$_arrayData);
            return response()->json([
                'data' => true,
                'message' => 'data update success'
            ]);
        }
        catch (\Throwable $th){
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');

        try {

            $sql = "select id from $this->table where id = ? and default_flag = 1 limit 1";
            $data = DB_global::cz_result_set($sql,[$id]);
            //exit();
            if(count($data) > 0){
                $message = "DEFAULT THEME CANNOT BE DELETED";
            }else{

                $_arrayData = array(
                    'is_deleted'=> 1
                );
                $updatedData = DB_global::cz_update($this->table,'id',$id,$_arrayData);

                $message = "DATA DELETED SUCCESS";
            }

            return response()->json([
                'data' => $data,
                'message' => $message
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data delete failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setAsDefault(Request $request){
        $id = $request->input("id");
        $platform_id = $request->input("platform_id");

        try {
            $_arrayData = array(
                'default_flag'=> 1
            );
            //code...
            DB_global::cz_update($this->table,'id',$id,$_arrayData);

            //update another data, set default 0
            //check another data exist or not
            $param = array(
                'id'=>$id,
                'platform_id'=>$platform_id
            );

            $sql = "select * from $this->table where id <> :id and platform_id = :platform_id ";
            $data = DB_global::cz_result_set($sql,$param,false,"COUNT");

            if($data > 0){
                $_anotherData = array(
                    'default_flag'=> 0
                );
                $array_where = [
                    ['id','<>',$id],
                    ['platform_id','=',$platform_id]
                ];
                DB_global::cz_update_where_array($this->table,$_anotherData,$array_where);
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
}
