<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
class SliderController extends Controller
{
    protected $table_name = 'slider';
    protected $folder_name = 'recognition/slider';
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $status_active = $request->input('status_active');

        $sql = "select * from slider where is_deleted = 0 and platform_id = :platform_id order by name, platform_id asc";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            if(isset($status_active)){
                $sql = "select * from slider where is_deleted = 0 and platform_id = :platform_id and status_active = $status_active order by name, platform_id asc";
            }
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
       $sql = "select * from slider where md5(id) = ? limit 1";

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

   public function RsSlider(Request $request){
        $where = $request->input("platform");
        $param = [];
        $str_where="";
        if($where!=""){
            $str_where= "and platform_id = ?";
            $param = array(
                $where
            );
        }

		$sql = "select * from slider where is_deleted = 0 and slider_image is not null and status_active = 1 ".$str_where." order by name";

        try {
            //code...
            $data = DB_global::cz_result_set($sql,$param);

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
        $_arrayData = $request->input();

        $file = $request->file('slider_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

        $file2 = $request->file('slider_file_mobile');
        $fileName2 = $request->input('user_account'). '_' .$file2->getClientOriginalName();
        $fileName2 = DB_global::cleanFileName($fileName2);
        Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');

        unset($_arrayData['user_account'] , $_arrayData['slider_file'], $_arrayData['slider_file_mobile']);
        $_arrayData = array_merge($_arrayData,
        array(
            'slider_image' => $fileName,
            'slider_image_mobile' => $fileName2,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert('slider',$_arrayData,false);
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

    public function UpdateData(Request $request)
    {
        $id = $request->input('id');
        $all = $request->except('user_account','id','slider_file','slider_file_mobile');

        if($request->hasFile('slider_file'))
        {
            $file = $request->file('slider_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['slider_image' => $fileName]);
        }

        if($request->hasFile('slider_file_mobile'))
        {
            $file2 = $request->file('slider_file_mobile');
            $fileName2 = $request->input('user_account'). '_' .$file2->getClientOriginalName();
            // $file2->move($tujuan_upload, $fileName2);
            $fileName2 = DB_global::cleanFileName($fileName2);
            Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
            $all = array_merge($all, ['slider_image_mobile' => $fileName2]);
        }

        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update('slider','id',$id,$all);

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
            $data = DB_global::bool_ValidateDataOnTableById_md5('slider',$id);
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
        $slider = $request->input('slider');
        $sql = "select * from slider where name = ?";
        try {
            //code...
            $data = DB_global::bool_CheckRowExist($sql,[$slider]);
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
            $hdr = DB_global::cz_update('slider', 'id', $id, $param);

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
