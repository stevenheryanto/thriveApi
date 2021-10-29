<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AdsController extends Controller
{
    protected $table_name = 'ads';
    protected $folder_name = 'recognition/ads';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $platform_id = $request->input('platform_id');

        $sql = "select * from ads where is_deleted = 0  
            and platform_id = :platform_id 
            order by name";
                    
        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT  :offset, :limit  ";
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
		$sql = "select * from ads where md5(id) = ? limit 1";

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
    
    public function AdsImage(Request $request)
    {	
        $platform_id = $request->input('platform_id');
        $sql = "select * from ads where platform_id=? and status_active = 1 and is_deleted = 0 and show_on_module=0 and is_published=1 and CURDATE() between valid_from and valid_to ORDER BY RAND() limit 1";

        try {
            $data = DB_global::cz_result_array($sql, [$platform_id]);
        
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

    public function AdsNewsfeedImage(Request $request)
    {	
        $platform_id = $request->input('platform_id');
        $sql = "select * from ads where platform_id=? and status_active = 1 and is_deleted = 0 and show_on_module=1 and is_published=1 and CURDATE() between valid_from and valid_to ORDER BY RAND() limit 1";

        try {
            $data = DB_global::cz_result_array($sql, [$platform_id]);
        
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

        $file = $request->file('ads_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        // $file->move($tujuan_upload, $fileName);
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['ads_file']); 
        $_arrayData = array_merge($_arrayData,
        array(
            'ads_image' => $fileName,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert('ads',$_arrayData,false);
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
        $all = $request->except('id','user_account','ads_file');

        if($request->hasFile('ads_file'))
        {
            $file = $request->file('ads_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            // $file->move($tujuan_upload, $fileName);
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['ads_image' => $fileName]);
        }
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update('ads','id',$id,$all);

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
            $data = DB_global::bool_ValidateDataOnTableById_md5('ads',$id);
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

    public function ValidateHashtag(Request $request)
    {	
        $slider = $request->input('slider');
        $sql = "select * from ads where name = ?";

        try {
            $data = DB_global::bool_CheckRowExist($sql, [$slider]);
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

    public function ClearAllIsPublished(Request $request)
    {	
        $show_on_module = $request->input('show_on_module');
        $platform_id = $request->input('platform_id');
        $sql = "update ads SET is_published=0 where show_on_module = :show_on_module and platform_id = :platform_id";

        try {
            $param = array(
                'show_on_module'=>$show_on_module,
                'platform_id'=>$platform_id,
            );

            $data = DB_global::cz_execute_query($sql, $param);
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

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $param = array(
                'is_deleted'=>1
            );
            $hdr = DB_global::cz_update('ads', 'id', $id, $param);

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
