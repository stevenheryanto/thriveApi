<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_curriculum;

class CurriculumController extends Controller
{
    
    protected $folder_name = 'learn/curriculum';
    protected $table_name = 'awb_mst_curriculum';

    public function ListData(Request $request)
    {
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $categoryId = $request->input('categoryId');
        $category   = $request->input('category');
        $sort_by    = $request->input('shortBy');
        $export     = $request->input('export');
        $platform_id     = $request->input('platform_id');


        $where = $request->input('role');
        $str_where 					= "";
        if($where !="")
	 	{
            $str_where = " and (
                a.role like :role
            )";
            $param = array(
                'role'=> '%'.$where.'%',
            );
        } else {
            $str_where = "";
            $param = [];
        }


        switch($sort_by)
		{
			case 'last_modified':
				$sort_by = "a.date_modified desc";
				break;
			
			default:
				$sort_by = "a.date_modified desc";
				break;
		}


        

		$sql = "select 
					a.*,
                    CASE
                    WHEN a.role = 1 THEN 'Project Team'
                    WHEN a.role = 2 THEN 'Governance Board'
                    END AS rolename
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
				$sort_by = "a.role desc";
				break;
			
			default:
				$sort_by = "a.role desc";
				break;
		}


        

		 $sql = "select 
					a.*,
                    CASE
                    WHEN a.role = 1 THEN 'Project Team'
                    WHEN a.role = 2 THEN 'Governance Board'
                    END AS rolename
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

        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        
        $file = $request->file('curriculum_image');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['curriculum_image'], $_arrayData['user_id']);

        $_arrayData = array_merge($_arrayData,
        array(
            'curriculum_image' => $fileName,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'user_created'=> $user_id,
            'platform_id'=> $platform_id
        ));
        try {
            $data = DB_global::cz_insert($this->table_name,$_arrayData,false);
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
        $id         = $request->input('id');
        $user_id    = $request->input('user_id');
        $all        = $request->except('user_account','id','curriculum_image','user_id');

        if($request->hasFile('curriculum_image'))
        {
            $file = $request->file('curriculum_image');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['curriculum_image' => $fileName]);
        }
        $all = array_merge($all,
        array(
            'user_modified'=> $user_id,
            'date_modified' => DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name,'id',$id,$all);

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
