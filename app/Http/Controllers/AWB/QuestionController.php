<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_question;

class QuestionController extends Controller
{
    protected $table_name = 'awb_mst_question';

    public function ListData(Request $request)
    {
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $sort_by    = $request->input('shortBy');
        $export     = $request->input('export');
        $platform_id     = $request->input('platform_id');

        $param = [];
        
        $examId = $request->input('examId');
        $whereexamId = "";
        if(!empty($examId))
        {
            $whereexamId = "and a.exam_id = :examId";
            $param =  array_merge($param,
            array(
                'examId'=>$examId
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

        $sql = "

            select 
                a.*,b.name as fullname, c.name as exam_name
            from " .  $this->table_name ." a 
                left join
                    users b on a.user_modified = b.id 
                left join 
                    awb_mst_exam c on a.exam_id = c.id 
            where 
                a.platform_id = :platform_id $whereexamId
            order by 
                a.seq

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
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');

        $sql = "update $this->table_name set sort_index = sort_index + 1
            where flag_active = 1
            and (sort_index >= (:sort_index - 1) and sort_index <> :sort_index2)
           ";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index
        ];

        $sql2 = "update $this->table_name set sort_index = sort_index - 1
            where flag_active = 1
            and id = :id
           ";
        $param2 = ['id'=>$id
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

        $sql = "update $this->table_name set sort_index = sort_index - 1
            where flag_active = 1
            and (sort_index <= (:sort_index + 1) and sort_index <> :sort_index2)
           ";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index
        ];
        $sql2 = "update $this->table_name set sort_index = sort_index + 1
            where flag_active = 1
            and id = :id";
        $param2 = ['id'=>$id
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

	function ReSortingIndex($platform_id)
	{
		$sql = "UPDATE $this->table_name a JOIN
			(SELECT t.*,
			        @rownum := @rownum + 1 AS row_idx
			    FROM $this->table_name  t,
			        (SELECT @rownum := 0) r
                WHERE flag_active = 1
                and platform_id = :platform_id
				order by sort_index
			 )  b on a.id= b.id
			SET a.sort_index = b.row_idx
            WHERE a.flag_active = 1
            and a.platform_id = :platform_id2
            ";
        $param = ['platform_id' => $platform_id,
                'platform_id2' => $platform_id
            ];
        $data = DB_global::cz_execute_query($sql, $param);
	}



    function ListSection(Request $request)
	{
        
		$sql 
            = 
            "
            SELECT 
                *
			FROM 
               awb_mst_question
			where 
                flag_active = 1 
                and platform_id = '".$request->input('platform_id')."'
			order by 
                title
            ";
        $category   = "";
        $param      = array();
        try {
           // $data = DB_global::cz_result_set($sql,"");
              
            $data = DB_global::cz_result_set($sql,$param,false,$category);

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

    function ListMenu(Request $request)
	{
        
        $whereMenuWithInfo = "";
        if($request->input('menu_with_info')){
            $whereMenuWithInfo = "and a.flag_menu_info = 1";
        }
		$sql = "
                select 
                    a.id,
                    concat(b.title,' - ' , a.title) as title
				from 
                    awb_mst_menu a 
                    left join awb_mst_question b on a.section_id = b.id 
				where 
                    a.flag_active = 1
                    and a.platform_id = '".$request->input('platform_id')."'
                    $whereMenuWithInfo
				order by 
                    b.id, b.title, a.title";
        $category   = "";
        $param      = array();
        try {
           // $data = DB_global::cz_result_set($sql,"");
              
            $data = DB_global::cz_result_set($sql,$param,false,$category);

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


    function ListCategory(Request $request)
	{
        $whereSpecialPage = "";
        if($request->input('special_page')){
            $whereSpecialPage = "and a.flag_special_page = 1";
        }
        $whereMenuInfo = "";
        if($request->input('menu_with_info')){
            $whereMenuInfo = "and b.flag_menu_info = 1";
        }
		$sql = "

             SELECT 
                a.id, 
                a.title as title_category, 
                b.id AS id_menu, 
                b.title AS title_menu, 
                c.id AS id_section, 
                c.title AS title_section
            FROM 
                awb_trn_category a, awb_mst_menu b, awb_mst_question c
            WHERE 
                a.menu_id = b.id
                AND b.section_id = c.id
                AND a.flag_active = '1'
                AND b.flag_active = '1'
                AND c.flag_active = '1' 
                and a.platform_id = '".$request->input('platform_id')."'
                and b.platform_id = '".$request->input('platform_id')."'
                and c.platform_id = '".$request->input('platform_id')."'
                $whereSpecialPage
                $whereMenuInfo
            order by
                c.title, b.title, a.title
           ";
        $category   = "";
        $param      = array();
        try {
           // $data = DB_global::cz_result_set($sql,"");
              
            $data = DB_global::cz_result_set($sql,$param,false,$category);

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
}
