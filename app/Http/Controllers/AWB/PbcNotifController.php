<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_ntf;

class PbcNotifController extends Controller
{
    protected $table_name = 'awb_mst_ntf';

    public function ListData(Request $request)
    {
        

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id   = $request->input('platform_id');
        $param      = [];
        $sql = "
             select 
                a.*,b.name as fullname
             from 
                " .$this->table_name ." a left join
                users b on a.user_modified = b.id 
            where
                a.platform_id = :platform_id
            order by 
                a.date_modified desc
       ";

       $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
            if ($category != "COUNT" && $export == false)
            {
                $sql = $sql . " LIMIT  :offset, :limit  ";
                //code...
                $param =  array_merge($param,
                    array(
                        'limit'=>$limit,
                        'offset'=>$offset,
                        'platform_id'=>$platform_id
                    )
                );
            }else{
                $param =  array_merge($param,
                array(
                    'platform_id'=>$platform_id
                )
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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();
        $_arrayData = array_merge($_arrayData,
            array(
                'date_created'=> DB_global::Global_CurrentDatetime(),
                'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert($this->table_name, $_arrayData, false);
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
        $all = $request->except('id','user_account');

        //var_dump($all);exit();
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name,'id', $id, $all);
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
		$sql = "select * from $this->table_name where md5(id) = ? limit 1";
        
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

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::cz_delete($this->table_name,'id',$id);
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


    public function MoveUp(Request $request)
	{
        $id         = $request->input('id');
        $currentIdx = $request->input('currentIdx');
      
        $sql = "update " . $this->table_name ." set sort_index = sort_index + 1 where (sort_index >= ($currentIdx - 1) and sort_index <> $currentIdx)";
        $sql2 = "update " . $this->table_name ." set sort_index = sort_index - 1 where id = :id";
        $param2     =   ['id'   =>  $id];


        try {
            $data   = DB_global::cz_execute_query($sql);
            $data2  = DB_global::cz_execute_query($sql2, $param2);            
            
            $this->ReSortingIndex();

            return response()->json([
                'data'      => $data,
                'data2'     => $data2,
                'message'   => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function MoveDown(Request $request)
	{
        $id         = $request->input('id');
        $currentIdx = $request->input('currentIdx');



             
        $sql = "update " . $this->table_name ." set sort_index = sort_index - 1 where (sort_index >= ($currentIdx + 1) and sort_index <> $currentIdx)";
		$param          =   array();
        
        $sql2 = "update " . $this->table_name ." set sort_index = sort_index + 1 where id = :id";
        $param2     =   ['id'   =>  $id];

        try {
            $data   = DB_global::cz_execute_query($sql);
            $data2  = DB_global::cz_execute_query($sql2, $param2);
           
            $this->ReSortingIndex();

            return response()->json([
                'data'      => $data,
                'data2'     => $data2,
                'message'   => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	function ReSortingIndex()
	{
		
        $sql =  "UPDATE " . $this->table_name ." a JOIN
                (SELECT t.*, 
                     @rownum := @rownum + 1 AS row_idx
                 FROM " . $this->table_name ."  t, 
                        (SELECT @rownum := 0) r
                order by sort_index
                )  b on a.id= b.id
                SET a.sort_index = b.row_idx 
				";


        $param  = array();
        $data3   = DB_global::cz_execute_query($sql);

        //ar_dump( $data);

	}
}
