<?php

namespace App\Http\Controllers\TTL;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class UftEmailCardController extends Controller
{
    protected $table_name = 'uft_email_card';
    protected $folder_name = 'listen/email_card';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $status_active = $request->input('status_active');

        $sql = "select * from $this->table_name where is_deleted = 0 and platform_id = :platform_id order by sort_index asc";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT")
        {
            if(isset($status_active)){
                $sql = "select * from $this->table_name where is_deleted = 0 and platform_id = :platform_id and status_active = $status_active order by sort_index asc";
            }
            $sql = $sql . " LIMIT :offset,:limit ";
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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();

        $file = $request->file('feature_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['feature_file']); 
        $_arrayData = array_merge($_arrayData,
        array(
            'feature_image' => $fileName,
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
        $all = $request->except('user_account','id','feature_file');

        if($request->hasFile('feature_file'))
        {
            $file = $request->file('feature_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');    
            $all = array_merge($all, ['feature_image' => $fileName]);
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

    public function MoveUp(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');

        $sql = "update $this->table_name set sort_index = sort_index + 1 
            where is_deleted = 0 
            and (sort_index >= (:sort_index - 1) and sort_index <> :sort_index2)
            and platform_id = :platform_id";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index,
            'platform_id' => $platform_id
        ];

        $sql2 = "update $this->table_name set sort_index = sort_index - 1 
            where is_deleted = 0 
            and id = :id 
            and platform_id = :platform_id";
        $param2 = ['id'=>$id,
            'platform_id'=>$platform_id
        ];
        try {
            $data = DB_global::cz_execute_query($sql, $param);
            $data2 = DB_global::cz_execute_query($sql2, $param2);
            /*$this->ReSortingIndex($platform_id);*/
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

	public function MoveDown(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');

        $sql = "update $this->table_name set sort_index = sort_index - 1 
            where is_deleted = 0 
            and (sort_index <= (:sort_index + 1) and sort_index <> :sort_index2)
            and platform_id = :platform_id";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index,
            'platform_id' => $platform_id
        ];
        $sql2 = "update $this->table_name set sort_index = sort_index + 1 
            where is_deleted = 0 
            and id = :id 
            and platform_id = :platform_id ";
        $param2 = ['id'=>$id,
            'platform_id'=>$platform_id
        ];
        try {
            $data = DB_global::cz_execute_query($sql, $param);
            $data2 = DB_global::cz_execute_query($sql2, $param2);

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

	function ReSortingIndex($platform_id)
	{
		$sql = "UPDATE $this->table_name a JOIN
			(SELECT t.*, 
			        @rownum := @rownum + 1 AS row_idx
			    FROM $this->table_name  t, 
			        (SELECT @rownum := 0) r
                WHERE is_deleted = 0
                and platform_id = :platform_id
				order by sort_index
			 )  b on a.id= b.id
			SET a.sort_index = b.row_idx
            WHERE a.is_deleted = 0 
            and a.platform_id = :platform_id2
            ";
        $param = ['platform_id' => $platform_id,
                'platform_id2' => $platform_id
            ];
        $data = DB_global::cz_execute_query($sql, $param);
	}
}
