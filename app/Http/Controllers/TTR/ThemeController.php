<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ThemeController extends Controller
{
    protected $folder_name = 'recognition/theme';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');


        $sql = "select a.* from recognize_theme a left join recognize_platform_hdr b on a.platform_id = b.id where b.status_active = 1 and b.id = :platform_id and a.is_deleted is null order by a.date_modified desc";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT  :offset,:limit ";
        }

        if ($category == "COLUMNS"){
            $sql2 = "show columns from recognize_theme";

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
        }

        try {
        //code...
            $param = array(
                'limit'=>$limit,
                'offset'=>$offset,
                'platform_id'=>$platform_id
            );
            if ($category == "COUNT")
            {
                //$param = $param->except('limit','offset');
                unset($param['limit']);
                unset($param['offset']);
            }
            #print $sql;
            $data = DB_global::cz_result_set($sql,$param,false,$category);

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
    }

    public function SelectData(Request $request)
    {
        $id = $request->input('md5ID');
        $sql = "select * from recognize_theme where md5(id) = ? limit 1";

        try {
            //code...
                $data = DB_global::cz_result_array($sql,[$id]);
                $columnTable = DB_global::cz_getTableColumns('recognize_theme');
                return response()->json([
                    'data' => $data,
                    'data2' => $columnTable,
                    'message' => 'success'
                ]);

            } catch (\Throwable $th) {
                //throw $th;
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
        $sql = "select * from recognize_theme where platform_id = :platform_id and status_active = 1 ";
        $param = ['platform_id' => $platform_id];
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
        try {
            if(!isset($listTheme)){
                /* for modal platform selection */
                /* get default theme is lang is not selected */
                $data = DB_global::cz_result_array($sql, $param);

            }else{
                $data = DB_global::cz_result_set($sql, $param);
            }
            $columnTable = DB_global::cz_getTableColumns('recognize_theme');
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


        $arrFieldImageFile = array(
            "background_profile","img_logo",
            "img_menu1","img_menu2",
            "img_menu3","img_menu4",
            "img_menu5","img_menu6",
            "img_menu7", "img_menu8", "img_email_notification",
            "img_crontab_header", "img_crontab_border",
            "img_crontab_footer", "img_crontab_button",
            "img_crontab_commentlike",
            "img_tab_icon_browser"
        );
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
            try {
                $data = DB_global::cz_insert('recognize_theme',$_arrayData,true);
                return response()->json([
                    'data' => true,
                    'message' => 'data insert success'
                ]);
            } catch (\Throwable $th) {
                //throw $th;
                return response()->json([
                    'data' => false,
                    'message' => 'data insert failed: '.$th
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        catch (\Throwable $th){
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }




        // $_arrayData = $request->except('user_account','background_profile_file');
        // $_arrayData = array_merge($_arrayData,
        // array(
        //     'background_profile' => $fileName,
        //     'date_modified'=> DB_global::Global_CurrentDatetime()
        // ));
        // try {
        //     $data = DB_global::cz_insert('recognize_theme',$_arrayData,true);
        //     return response()->json([
        //         'data' => true,
        //         'message' => 'data insert success'
        //     ]);
        // } catch (\Throwable $th) {
        //     //throw $th;
        //     return response()->json([
        //         'data' => false,
        //         'message' => 'data insert failed: '.$th
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
    }

    public function  UpdateData(Request $request)
    {
        $id = $request->input('id');
        $_arrayData = $request->except('id','user_account');
        $arrFieldImageFile = array(
            "background_profile","img_logo",
            "img_menu1","img_menu2",
            "img_menu3","img_menu4",
            "img_menu5","img_menu6",
            "img_menu7", "img_menu8", "img_email_notification",
            "img_crontab_header", "img_crontab_border",
            "img_crontab_footer", "img_crontab_button",
            "img_crontab_commentlike",
            "img_tab_icon_browser"
        );

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
            try {
                $data = DB_global::cz_update('recognize_theme','id',$id,$_arrayData);
                return response()->json([
                    'data' => true,
                    'message' => 'data update success'
                ]);
            } catch (\Throwable $th) {
                //throw $th;
                return response()->json([
                    'data' => false,
                    'message' => 'data insert failed: '.$th
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        catch (\Throwable $th){
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // try {
        //     $data = DB_global::cz_update('recognize_theme','id',$id,$_arrayData);
        //     return response()->json([
        //         'data' => true,
        //         'message' => 'data update success'
        //     ]);
        // } catch (\Throwable $th) {
        //     //throw $th;
        //     return response()->json([
        //         'data' => false,
        //         'message' => 'data update failed: '.$th
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
    }

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            //code...
            $data = DB_global::bool_ValidateDataOnTableById_md5('recognize_theme',$id);
            //$columnTable = DB_global::cz_getTableColumns('recognize_theme');
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
    }

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {

            $sql = "select id from recognize_theme where id = ? and default_flag = 1 limit 1";
            $data = DB_global::cz_result_set($sql,[$id]);
            //exit();
            if(count($data) > 0){
                $message = "DEFAULT THEME CANNOT BE DELETED";
            }else{

                $_arrayData = array(
                    'is_deleted'=> 1
                );
                $updatedData = DB_global::cz_update('recognize_theme','id',$id,$_arrayData);
                // $sql = "select id from recognize_platform_hdr a left join recognize_theme b on a.id = b.platform_id where theme_id = ?";
                // $data = DB_global::cz_result_set($sql,[$id],false);
                // foreach($data as $newData){
                //     //echo "<pre>";print_r($newData->id);

                // }
                // //echo "<pre>";print_r($updatedData);
                // $dtl1 = DB_global::cz_delete('recognize_theme','id',$id);

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
        $table = "recognize_theme";
        try {
            $_arrayData = array(
                'default_flag'=> 1
            );
            //code...
            DB_global::cz_update($table,'id',$id,$_arrayData);

            //update another data, set default 0
            //check another data exist or not
            $param = array(
                'id'=>$id,
                'platform_id'=>$platform_id
            );

            $sql = "select * from $table where id <> :id and platform_id = :platform_id ";
            $data = DB_global::cz_result_set($sql,$param,false,"COUNT");

            if($data > 0){
                $_anotherData = array(
                    'default_flag'=> 0
                );
                $array_where = [
                    ['id','<>',$id],
                    ['platform_id','=',$platform_id]
                ];
                DB_global::cz_update_where_array($table,$_anotherData,$array_where);
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
