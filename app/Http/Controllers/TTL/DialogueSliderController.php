<?php

namespace App\Http\Controllers\TTL;

use DB_global;
use App\Models\TTL\dialogue_slider;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DialogueSliderController extends Controller
{
    protected $table_name = 'dialogue_slider';
    protected $folder_name = 'listen/slider';

    public function ListData(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $status_active = $request->input('status_active');

        try {
            $query = dialogue_slider::select('*')
                ->where('platform_id', '=', $platform_id)
                ->where('is_deleted', '=', 0)
                ->when(isset($status_active), 
                    function ($query) use($status_active) {
                    $query->where('status_active', '=', $status_active);
                });
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $data = $query->count();
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

    public function SelectData(Request $request)
    {
        $id = $request->input('md5ID');
        $sql = "select * from $this->table_name where md5(id) = ? limit 1";

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

    public function RsSlider(Request $request)
    {
        $where = $request->input("platform");
        $param = [];
        $str_where="";
        if($where!=""){
            $str_where= "and platform_id = ?";
            $param = array(
                $where
            );
        }

		$sql = "select * from $this->table_name where is_deleted = 0 and slider_image is not null and status_active = 1 ".$str_where." order by name";

        try {
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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();

        $file = $request->file('slider_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['slider_file']); 
        $_arrayData = array_merge($_arrayData,
        array(
            'slider_image' => $fileName,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert($this->table_name, $_arrayData,false);
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
        $all = $request->except('user_account','id','slider_file');

        if($request->hasFile('slider_file'))
        {
            $file = $request->file('slider_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');    
            $all = array_merge($all, ['slider_image' => $fileName]);
        }
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name, 'id', $id, $all);

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
            $data = DB_global::bool_ValidateDataOnTableById_md5($this->table_name, $id);
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
        $sql = "select * from $this->table_name where name = ?";
        try {
            //code...
            $data = DB_global::bool_CheckRowExist($sql,[$slider]);
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
            $hdr = DB_global::cz_update($this->table_name, 'id', $id, $param);

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
