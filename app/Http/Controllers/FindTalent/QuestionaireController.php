<?php

namespace App\Http\Controllers\FindTalent;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\FindTalent\findtalent_project_questionnaire;

class QuestionaireController extends Controller
{
    
    protected $folder_name  = 'findtalent/project';
    protected $table_name   = 'findtalent_project_questionnaire';

    public function ListData(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');

        $project_id    = $request->input('projectIdForAdmin');

        $status_active  = $request->input('status_active');

        $param = [];

        $whereAktiv = "";
        if(isset($status_active)){
            $whereAktiv = "and status_active = :status_active";
           
                $param =  array_merge($param,
                    array(
                        'status_active'=>$status_active,
                    )
                );
        }
        

         $sql = "
            select
                * ,                
                CONCAT( question_type, '_', id ) as question_type_plus_id
            from 
                ".$this->table_name." 
            where 
                project_id = :project_id  $whereAktiv 
            order by 
                question
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
                        'project_id'=>$project_id
                    )
                );
            }else{
                $param =  array_merge($param,
                    array(
                        'project_id'=>$project_id
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

    public function ListDataForId(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');

        $project_id    = $request->input('projectIdForAdmin');

        $status_active  = $request->input('status_active');

        $param = [];

        $whereAktiv = "";
        if(isset($status_active)){
            $whereAktiv = "and status_active = :status_active";
           
                $param =  array_merge($param,
                    array(
                        'status_active'=>$status_active,
                    )
                );
        }
        

         $sql = "
            select               
                CONCAT( question_type, '_', id ) as question_type_plus_id
            from 
                ".$this->table_name." 
            where 
                project_id = :project_id  $whereAktiv 
            order by 
                question
            ";
                    
            $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
            if ($category != "COUNT" && $export == false)
            {
               // $sql = $sql . " LIMIT  :offset, :limit  ";
                //code...
                $param =  array_merge($param,
                    array(
                        'project_id'=>$project_id
                    )
                );
            }else{
                $param =  array_merge($param,
                    array(
                        'project_id'=>$project_id
                    )
                );
           }    

           //echo $sql;

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
        $_arrayData         = $request->except('functionPublish','employeePublish','user_id');
        $user_created       = $request->input('user_created');

      //ads;asd
        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'  => DB_global::Global_CurrentDatetime(),
            'user_created'  => $user_created
        ));
        try {
            $newId = DB_global::cz_insert($this->table_name,$_arrayData,true);
            
            switch ($request->input('flag_publish_by_employee')) {
                case 1:
                    $this->publishForAll($request->input('user_created'), $newId);
                break;
                case 2:
                    $this->publishForFunction($request->input('functionPublish'), $request->input('user_created'), $newId);
                break;
                case 3:
                    $arrayEmployeePublish = json_decode($request->input('employeePublish'));
                    $this->publishForEmployee($arrayEmployeePublish, $request->input('user_created'), $newId); 
                break;
                default:
                    return true;
            }
           

            return response()->json([
                'data' => false,
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
        $all        = $request->except('functionPublish','employeePublish','user_id');

        $all = array_merge($all,
        array(
            'user_modified'=> $user_id,
            'date_modified' => DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name,'id',$id,$all);

            
            $this->deleteParticipant($id);

            switch ($request->input('flag_publish_by_employee')) {
                case 1:
                    $this->publishForAll($user_id, $id);
                break;
                case 2:
                    $this->publishForFunction($request->input('functionPublish'),$user_id, $id);
                break;
                case 3:
                    
                    $arrayEmployeePublish = json_decode($request->input('employeePublish'));
                    $this->publishForEmployee($arrayEmployeePublish,  $user_id, $id); 
                break;
                default:
                    return true;
            }

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

    public function publishForAll($user_created,$projectId){

        $_arrayData =
            array(
                'findtalent_project_questionnaire_id' => $projectId,
                'imdl_id'           => '0',
                'user_function'         => '1',
                'status_active'           => '1',
                'user_created'          => $user_created,
                'date_created'          => DB_global::Global_CurrentDatetime()
            );
        try {
            $data = DB_global::cz_insert('findtalent_project_questionnaire_participant', $_arrayData, false);
            return true;
        } catch (\Throwable $th) {
            return false;
        }

    }
    public function publishForFunction($functionPublish,$user_created,$projectId){

        $_arrayData =
            array(
                'findtalent_project_questionnaire_id' => $projectId,
                'imdl_id'           => '0',
                'user_function'         => $functionPublish,
                'status_active'         => '1',
                'user_created'          => $user_created,
                'date_created'          => DB_global::Global_CurrentDatetime()
            );
        try {
            $data = DB_global::cz_insert('findtalent_project_questionnaire_participant', $_arrayData, false);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function publishForEmployee($arrayDatas,$user_created,$projectId){
        if(count($arrayDatas) > 0){
            foreach($arrayDatas as $arrayData){
                $arrayInsert = array(
                    'findtalent_project_questionnaire_id' => $projectId,
                    'imdl_id'           => $arrayData->value,
                    'user_function'         => '3',
                    'status_active'         => '1',
                    'user_created'          => $user_created,
                    'date_created'          => DB_global::Global_CurrentDatetime()
                );
                DB_global::cz_insert('findtalent_project_questionnaire_participant', $arrayInsert, true);
            }
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

    public function SelectDataParticipantByProjectId(Request $request)
	{
        $id     = $request->input('md5ID');
		$sql    = "select * from findtalent_project_questionnaire_participant where md5(findtalent_project_questionnaire_id) = ? limit 1";
        
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
    public function deleteParticipant($id)
    {
        
        try {
            $data = DB_global::cz_delete('findtalent_project_questionnaire_participant','findtalent_project_questionnaire_id',$id);
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
