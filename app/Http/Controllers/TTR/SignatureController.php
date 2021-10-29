<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    protected $table_name = 'signature';
    protected $folder_name = 'recognition/signature';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');


       $sql = "select * from signature where is_deleted = 0 and platform_id = :platform_id order by signature, platform_id asc";

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
       $sql = "select * from signature where md5(id) = ? limit 1";

       try {
        //code...
            $data = DB_global::cz_result_array($sql,[$id]);

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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->except('user_account','signature_file','signature_file2');

        $file = $request->file('signature_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

        $file2 = $request->file('signature_file2');
        $fileName2 = $request->input('user_account'). '_' .$file2->getClientOriginalName();
        $fileName2 = DB_global::cleanFileName($fileName2);
        Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');

        $_arrayData = array_merge($_arrayData,
        array(
            'preview_image' => $fileName,
            'preview_image_ind' => $fileName2,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert('signature',$_arrayData,true);
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

   public function  UpdateData(Request $request)
    {
        $id = $request->input('id');
        $all = $request->except('id','user_account','signature_file','signature_file2');

        $tujuan_upload = 'signature';
        if($request->hasFile('signature_file'))
        {
            $file = $request->file('signature_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            // $file->move($tujuan_upload, $fileName);
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');    
            $all = array_merge($all, ['preview_image' => $fileName]);
        }
        if($request->hasFile('signature_file2'))
        {
            $file2 = $request->file('signature_file2');
            $fileName2 = $request->input('user_account'). '_' .$file2->getClientOriginalName();
            // $file2->move($tujuan_upload, $fileName2);
            $fileName2 = DB_global::cleanFileName($fileName2);
            Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
            $all = array_merge($all, ['preview_image_ind' => $fileName2]);
        }
        $_arrayData = array_merge($all,['date_modified'=> DB_global::Global_CurrentDatetime()]);

        try {
            $data = DB_global::cz_update('signature','id',$id,$_arrayData);
            return response()->json([
                'data' => true,
                'message' => 'data update success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
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
            //code...
            $data = DB_global::bool_ValidateDataOnTableById_md5('signature',$id);
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

    public function ValidateHashtag(Request $request)
    {
        $slider = $request->input('signature');
        $platform_id = $request->input('platform_id');
        $sql = "select * from signature where signature = ? and platform_id = ?";
        try {
            //code...
            $data = DB_global::bool_CheckRowExist($sql,[$slider,$platform_id]);
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
            $param = array(
                'is_deleted'=>1
            );
            $hdr = DB_global::cz_update('signature', 'id', $id, $param);

            return response()->json([
                'data' => true,
                'message' => 'data delete success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data delete failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
