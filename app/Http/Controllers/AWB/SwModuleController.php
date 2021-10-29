<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\sw_mst_module;

class SwModuleController extends Controller
{
    protected $table_name = 'sw_mst_module';

    public function ListData(Request $request)
    {
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $categoryId = $request->input('categoryId');
        $category   = $request->input('category');
        $sort_by    = $request->input('shortBy');
        $export     = $request->input('export');
        $platform_id     = $request->input('platform_id');


        $where      = $request->input('str_where');
        $str_where  = "";
        if($where !="")
	 	{
            $str_where = " and (
                a.name like :name
            )";
            $param = array(
                'name'=> '%'.$where.'%',
            );
        } else {
            $str_where  = "";
            $param      = [];
        }

        

        $curriculumId = $request->input('curriculumId');
        $whereCurriculumId = "";
        if(!empty($curriculumId))
        {
            $whereCurriculumId = "and a.curriculum_id = :curriculumId";
            $param =  array_merge($param,
            array(
                'curriculumId'=>$curriculumId
            )
        );
        }


        switch($sort_by)
		{
			case 'name':
				$sort_by = "a.name desc";
				break;
			default:
				$sort_by = "a.date_modified desc";
				break;
		}

		$sql = "select 
					a.*					
                from " .  $this->table_name ." a 
				where 
					1=1 
                    and a.platform_id = :platform_id 
                    ".$str_where."  ".$whereCurriculumId."
				order by 
					$sort_by
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
   
    public function ListDataForSelectOption(Request $request)
    {
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $categoryId = $request->input('categoryId');
        $category   = $request->input('category');
        $sort_by    = $request->input('shortBy');
        $platform_id     = $request->input('platform_id');


        $where = $request->input('role');
        $str_where 					= "";
        $param = [];
        


        switch($sort_by)
		{
			case 'last_modified':
				$sort_by = "a.id";
				break;
			
			default:
                $sort_by = "a.id";
				break;
		}


        

		 $sql = "select 
					a.*
				from 
                    ".$this->table_name ." a
				where 
					1=1 
                    and a.platform_id = :platform_id 
                    ".$str_where." 
				order by 
					$sort_by
			";
     
            $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
            if ($category != "COUNT" )
            {
                //$sql = $sql . " LIMIT  :offset, :limit  ";
                //code...
                $param =  array_merge($param,
                    array(
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

}
