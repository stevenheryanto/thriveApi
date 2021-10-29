<?php

namespace App\Http\Controllers\FindTalent;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\FindTalent\findtalent_project;
use PHPMailer\PHPMailer\PHPMailer;

class ProjectController extends Controller
{
    
    protected $folder_name  = 'findtalent/project';
    protected $table_name   = 'findtalent_project';

    protected $amazoneLink;
    protected $appLink;

    protected $fromName ;
    protected $usernameEmail;
    protected $passwordEmail;
    protected $hostEmail;
    protected $portEmail;
    protected $noReply ;

    protected $serviceURL;

    public function __construct()
    {
        $this->amazoneLink   = 'https://thrive-frontend-dev.s3-eu-west-1.amazonaws.com/findtalent/';
        $this->appLink       = 'https://findtalent.dev-culture.pmicloud.biz/';

        $this->fromName         =   'Find Talent';
        $this->usernameEmail    =   env('MAIL_USERNAME');
        $this->passwordEmail    =   env('MAIL_PASSWORD');
        $this->hostEmail        =   'email-smtp.eu-west-1.amazonaws.com';
        $this->portEmail        =   '587';
        $this->noReply          =   'findtalent.no-reply@dev-culture.pmicloud.biz';
    }

    public function ListData(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');

        $platform_id    = $request->input('platform_id');

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
                * 
            from ".$this->table_name." 
            where 
                platform_id = :platform_id  $whereAktiv 
            order by 
                title
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
                'cekEnv' => $_ENV,
                'message' => 'success'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListDataForUserByStatusProject(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');

        $platform_id    = $request->input('platform_id');
        $imdl_id        = $request->input('imdl_id');

        $status_active      = $request->input('status_active');
        $status_project     = $request->input('status_project');

        $param = [];

        $whereAktiv = "";
        if(isset($status_active)){
            $whereAktiv = "and findtalent_project.status_active = :status_active";
           
                $param =  array_merge($param,
                    array(
                        'status_active'=>$status_active,
                    )
                );
        }

        if($status_project == 'Saved as Draft'){
            $whereStatusProject = "and findtalent_project_user.status_project = 'Saved as Draft'";
        }
        else{
            $whereStatusProject = "and findtalent_project_user.status_project != 'Saved as Draft'";
        }
        

         $sql = "
            select 
                * 
            from 
                findtalent_project,
                findtalent_project_user
            where 
                findtalent_project.platform_id = :platform_id 
                and findtalent_project_user.project_id = findtalent_project.id 
                and findtalent_project_user.imdl_id_applied = :imdl_id                
                $whereAktiv 
                $whereStatusProject
            order by 
                findtalent_project.title
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
                        'platform_id'=>$platform_id,
                        'imdl_id'=>$imdl_id,
                    )
                );
            }else{
                $param =  array_merge($param,
                    array(
                        'platform_id'=>$platform_id,
                        'imdl_id'=>$imdl_id,
                        
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

    public function ListDataForHome(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');

        $platform_id        = $request->input('platform_id');
        $imdl_id            = $request->input('imdl_id');
        $user_function      = $request->input('user_function');

        $param = [];

         $sql = "

            select 
                x.*
            FROM 
                findtalent_project x
            where 
                platform_id = :platform_id
                and x.is_deleted = 0 
                and x.status_active = 1
                and x.id in 
                    (
                        select 
                            findtalent_project_id 
                        from 
                            findtalent_project_participant 
                        where 
                            imdl_id = :imdl_id
                                or 
                            user_function = :user_function 
                                or 
                            user_function = '1'
                    )
            order by 
                x.status_active desc, x.publish_index
           
            ";
                    
            $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
            if ($category != "COUNT" && $export == false)
            {
                $sql = $sql . " LIMIT  :offset, :limit  ";
                //code...
                $param =  array_merge($param,
                    array(
                        'limit'         =>$limit,
                        'offset'        =>$offset,
                        'platform_id'   =>$platform_id,
                        'imdl_id'       =>$imdl_id,
                        'user_function' =>$user_function
                    )
                );
            }else{
                $param =  array_merge($param,
                    array(
                        'platform_id'   =>$platform_id,
                        'imdl_id'       =>$imdl_id,
                        'user_function' =>$user_function
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

    public function ListDataParticipantByProjectId(Request $request)
	{
        $projectId    = $request->input('md5ID');
        $param = [];


            $sql = "
           
            select 
                b.id,b.account,b.name
            from 
                findtalent_project_participant a,
                users b
            where 
                a.imdl_id = b.id
                and md5(findtalent_project_id) = :findtalent_project_id
            ";
            $param =  array_merge($param,
                array(
                    'findtalent_project_id'=>$projectId
                )
            );
            

        try {

            $data = DB_global::cz_result_set($sql,$param,false,"");
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

    public function ListDataUserDirectorateByPlatform(Request $request)
    {

        $platform_id    = $request->input('platform_id');
        $param = [];


         $sql = "
           
            SELECT 
                distinct(users.directorate) 
            FROM 
                users 
            where 
                users.country in (select a.country from findtalent_platform_dtl_1 a where a.platform_id = :platform_id) 
                and  users.directorate != ''
            order by 
                users.directorate
            ";
            $param =  array_merge($param,
                array(
                    'platform_id'=>$platform_id
                )
            );
            

        try {

            $data = DB_global::cz_result_set($sql,$param,false,"");
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
        $themeId            = $request->input('themeId');
        $_arrayData         = $request->except('functionPublish','themeId','employeePublish','user_id'.'themeId');
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
                    $this->publishForEmployee($arrayEmployeePublish, $request->input('user_created'), $newId,$themeId); 
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
        $themeId    = $request->input('themeId');
        $all        = $request->except('functionPublish','employeePublish','user_id','themeId');

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
                    $this->publishForFunction($request->input('functionPublish'),$user_id, $id,$themeId);
                break;
                case 3:
                    
                    $arrayEmployeePublish = json_decode($request->input('employeePublish'));
                    $this->publishForEmployee($arrayEmployeePublish,  $user_id, $id, $themeId); 
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
                'findtalent_project_id' => $projectId,
                'imdl_id'           => '0',
                'user_function'         => '1',
                'status_active'           => '1',
                'user_created'          => $user_created,
                'date_created'          => DB_global::Global_CurrentDatetime()
            );
        try {
            $data = DB_global::cz_insert('findtalent_project_participant', $_arrayData, false);
            return true;
        } catch (\Throwable $th) {
            return false;
        }

    }
    public function publishForFunction($functionPublish,$user_created,$projectId){

        $_arrayData =
            array(
                'findtalent_project_id' => $projectId,
                'imdl_id'           => '0',
                'user_function'         => $functionPublish,
                'status_active'         => '1',
                'user_created'          => $user_created,
                'date_created'          => DB_global::Global_CurrentDatetime()
            );
        try {
            $data = DB_global::cz_insert('findtalent_project_participant', $_arrayData, false);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function publishForEmployee($arrayDatas,$user_created,$projectId,$themeId){
        if(count($arrayDatas) > 0){
            foreach($arrayDatas as $arrayData){
                $arrayInsert = array(
                    'findtalent_project_id' => $projectId,
                    'imdl_id'               => $arrayData->value,
                    'user_function'         => '3',
                    'status_active'         => '1',
                    'user_created'          => $user_created,
                    'date_created'          => DB_global::Global_CurrentDatetime()
                );
                DB_global::cz_insert('findtalent_project_participant', $arrayInsert, true);

                $this->sendEmailProjectPublishForUser($themeId,$projectId,$arrayData->value);
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
        $imdl_id_applied = $request->input('imdl_id_applied');
		$sql = "
                select 
                    $this->table_name.* ,
                    fpu.status_project
                from 
                    $this->table_name 
                    left join 
                        findtalent_project_user fpu 
                        on 
                            fpu.project_id = $this->table_name.id
                            and fpu.imdl_id_applied = ?
                where 
                    md5($this->table_name.id) = ? 
                limit 
                    1
                ";
        
        try {
            $data = DB_global::cz_result_array($sql, [$imdl_id_applied,$id]);

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

    public function SelectDataFor(Request $request)
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
		$sql    = "select * from findtalent_project_participant where md5(findtalent_project_id) = ? limit 1";
        
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
            $data = DB_global::cz_delete('findtalent_project_participant','findtalent_project_id',$id);
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

    public function SubmitApplied(Request $request)
	{
        

        $sql            = "select * from findtalent_project_user where project_id = ? and imdl_id_applied = ? limit 1";
        $cekDataExist   = DB_global::cz_result_array($sql, [$request->input('projectIdForAdmin'), $request->input('imdl_id_applied')]);

        if( $cekDataExist ){
            $newId = $cekDataExist['id'];

            $_arrayDataUpdateStatus = array(
                'status_project' => 'On Hold',
                'date_applied'      => DB_global::Global_CurrentDatetime(),
                'user_modified'  => $request->input('imdl_id_applied'),
                'date_modified'  => DB_global::Global_CurrentDatetime()
            );
            $data = DB_global::cz_update('findtalent_project_user','id',$newId,$_arrayDataUpdateStatus);
        }
        else{
            $_arrayData = array(
                'project_id'        => $request->input('projectIdForAdmin'),
                'imdl_id_applied'   => $request->input('imdl_id_applied'),
                'date_applied'      => DB_global::Global_CurrentDatetime(),
                'user_created'      => $request->input('imdl_id_applied'),
                'date_created'      => DB_global::Global_CurrentDatetime(),
                'status_project'    => 'On Hold'
            );
            $newId = DB_global::cz_insert('findtalent_project_user',$_arrayData,true);
        }
        
        try {
            $where      = $request->input('str_where');
            $limit      = $request->input('limit');
            $offset     = $request->input('offset');
            $category   = $request->input('category');
            $export     = $request->input('export');

            $project_id    = $request->input('projectIdForAdmin');

            $param = [];

            $sql    = "
            select
                * ,                
                CONCAT( question_type, '_', id ) as question_type_plus_id
            from 
                findtalent_project_questionnaire
            where 
                project_id = :project_id 
                and status_active = 1
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

    
            $datas = DB_global::cz_result_set($sql,$param,false,$category);
            foreach( $datas as $data){
                $id     = $data->id;
                $sql    = "select * from findtalent_project_questionnaire where id = ? limit 1";
          
                $dataQuestion   = DB_global::cz_result_array($sql, [$id]);

                $_arrayDataAnswer = 
				array(
                    'project_user'      => $newId,
                    'question'          => $dataQuestion['question'],
                    'answer'            => $request->input($data->question_type_plus_id),
                    'user_created'      => $request->input('imdl_id_applied'),
                    'date_created'      => DB_global::Global_CurrentDatetime()
                );
                
                $insertQuestionnaireUser = DB_global::cz_insert('findtalent_project_user_questionnaire',$_arrayDataAnswer,true);
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

    public function saveAsDraft(Request $request)
	{
        
        $_arrayData = 
            array(
                'project_id'        => $request->input('projectIdForAdmin'),
                'imdl_id_applied'   => $request->input('imdl_id_applied'),
                'user_created'      => $request->input('imdl_id_applied'),
                'date_created'      => DB_global::Global_CurrentDatetime(),
                'status_project'    => 'Saved as Draft'
            );
        try {
            $newId = DB_global::cz_insert('findtalent_project_user',$_arrayData,true);

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

    public function UpdateStatusProject(Request $request)
    {
        $id         = $request->input('id');
        $user_id    = $request->input('user_id');

        $themeId        = $request->input('themeId');
        
        $sqlProjectUser     = "select * from findtalent_project_user where id = ? limit 1";
        $dataProjectUser    = DB_global::cz_result_array($sqlProjectUser, [$id]);

            $all =  array(
                        'status_project'    => $request->input('status_project'),
                        'user_modified'     => $user_id,
                        'date_modified'     => DB_global::Global_CurrentDatetime()
                    );
        try {
            $data = DB_global::cz_update('findtalent_project_user','id',$id,$all);
            
            if($request->input('status_project') == 'Accepted'){
                $this->sendEmailProjectApproved($themeId,$dataProjectUser['project_id'],$dataProjectUser['imdl_id_applied']);
            }
            if($request->input('status_project') == 'Rejected'){
                $this->sendEmailProjectRejected($themeId,$dataProjectUser['project_id'],$dataProjectUser['imdl_id_applied']);
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

    public function sendEmailProjectPublishForUser($themeId,$projectId,$imdlId){

        $sqlProject     = "select * from findtalent_project where id = ? limit 1";
        $dataProject    = DB_global::cz_result_array($sqlProject, [$projectId]);

        $sqlTheme       = "select * from findtalent_theme where id = ? limit 1";
        $dataTheme      = DB_global::cz_result_array($sqlTheme, [$themeId]);

        $sqlUser        = "select * from users where id = ? limit 1";
        $dataUser       = DB_global::cz_result_array($sqlUser, [$imdlId]);

        $toEmail        =   $dataUser['email'];

        $imageLogo      = $this->amazoneLink.'theme/'.$dataTheme['image_logo'];
        $subject        = $dataTheme['text_email_subject_publish_project'];

        $messageBody    =   str_replace('<project_title>', $dataProject['title'], $dataTheme['text_email_body_publish']);
        $messageBody    =   str_replace('<full_name>', $dataUser['full_name'], $messageBody);
        $messageBody    =   str_replace('<description>', $dataProject['description'], $messageBody);
        $messageBody    =   str_replace('<start_date>', $dataProject['start_date'], $messageBody);
        $messageBody    =   str_replace('<duration_length>', $dataProject['duration_length'], $messageBody);
        $messageBody    =   str_replace('<duration_period_type>', $dataProject['duration_period_type'], $messageBody);
        $messageBody    =   str_replace('<avg_time_needed>', $dataProject['avg_time_needed'], $messageBody);
        $messageBody    =   str_replace('<registation_closed_by>', $dataProject['registation_closed_by'], $messageBody);
        $messageBody    =   str_replace('<project_manager>', $dataProject['project_manager'], $messageBody);

        $bodyHtml           = "<html xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns:m='http://schemas.microsoft.com/office/2004/12/omml' xmlns='http://www.w3.org/TR/REC-html40'>

        <head>
            <META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=us-ascii'>
            <meta name=Generator content='Microsoft Word 15 (filtered medium)'>
            <![if !mso]><style>v\:* {behavior:url(#default#VML);}
        o\:* {behavior:url(#default#VML);}
        w\:* {behavior:url(#default#VML);}
        .shape {behavior:url(#default#VML);}
        </style><![endif]>
        <style>
        /* Font Definitions */

        @font-face {
            font-family: 'Cambria Math';
            panose-1: 2 4 5 3 5 4 6 3 2 4;
        }

        @font-face {
            font-family: Calibri;
            panose-1: 2 15 5 2 2 2 4 3 2 4;
        }
        /* Style Definitions */

        p.MsoNormal,
        li.MsoNormal,
        div.MsoNormal {
            margin: 0in;
            margin-bottom: .0001pt;
            font-size: 11.0pt;
            font-family: 'Calibri', sans-serif;
        }

        a:link,
        span.MsoHyperlink {
            mso-style-priority: 99;
            color: blue;
            text-decoration: underline;
        }

        a:visited,
        span.MsoHyperlinkFollowed {
            mso-style-priority: 99;
            color: purple;
            text-decoration: underline;
        }

        p.msonormal0,
        li.msonormal0,
        div.msonormal0 {
            mso-style-name: msonormal;
            mso-margin-top-alt: auto;
            margin-right: 0in;
            mso-margin-bottom-alt: auto;
            margin-left: 0in;
            font-size: 11.0pt;
            font-family: 'Calibri', sans-serif;
        }

        span.EmailStyle18 {
            mso-style-type: personal;
            font-family: 'Calibri', sans-serif;
            color: windowtext;
        }

        span.EmailStyle19 {
            mso-style-type: personal-reply;
            font-family: 'Calibri', sans-serif;
            color: windowtext;
        }

        .MsoChpDefault {
            mso-style-type: export-only;
            font-size: 10.0pt;
        }

        @page WordSection1 {
            size: 8.5in 11.0in;
            margin: 1.0in 1.0in 1.0in 1.0in;
        }

        div.WordSection1 {
            page: WordSection1;
        }
        hr.colored {
            border: 0;  
            height: 2px;
            background: #000;
        }
        </style>
        <![if gte mso 9]><xml>
        <o:shapedefaults v:ext='edit' spidmax='1028' />
        </xml><![endif]>
        <![if gte mso 9]><xml>
        <o:shapelayout v:ext='edit'>
        <o:idmap v:ext='edit' data='1' />
        </o:shapelayout></xml><![endif]>
        </head>

        <body lang=EN-US link=blue vlink=purple>
        <table class=MsoNormalTable border=0 cellspacing=3 cellpadding=0>
            <tr>
                <td style='width:33%'>
                </td>
                <td style='width:33%'>
                </td>
                <td style='width:33%;text-align:right;padding:0 20px'>
                    <a href='" . $this->appLink . "'><img src='".$imageLogo."'></a>
                </td>
            </tr>
            <tr style='margin-top:10px;margin-bottom:10px;'>
                <td colspan='3'>
                    <hr class='colored'/>
                </td>
            </tr>
            <tr>
                <td colspan='3' style='font-family:Calibri,Arial;font-size:18px;padding:0 30px;text-align:justify;'>
                    ".$messageBody."
                </td>
            </tr>
        </table>
        </body>
        ";
        $bodyText =  "Email Test\r\nThis email was sent through the
        Amazon SES SMTP interface using the PHPMailer class.";
        
       

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->setFrom($this->noReply, $this->fromName);
        $mail->Username   = $this->usernameEmail;    
        $mail->Password   = $this->passwordEmail;
        $mail->Host       = $this->hostEmail;
        $mail->Port       = $this->portEmail;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'tls';
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject    = $subject;
        $mail->Body       = $bodyHtml;
        $mail->AltBody    = $bodyText;
        $mail->Send();

        $array_log = array(
            'sender_name'   =>  $this->fromName,
            'subject'       =>  $subject,
            'email_to'      =>  $toEmail,
            'email_body'    =>  $bodyHtml,
            'flag_email'    =>  1,
            'date_email'    =>  DB_global::Global_CurrentDatetime(),
            'transaction_id'=>  $projectId);
        DB_global::InsertEmailLog($array_log);
        
    }

    public function sendEmailProjectApproved($themeId,$projectId,$imdlId){

        $sqlProject     = "select * from findtalent_project where id = ? limit 1";
        $dataProject    = DB_global::cz_result_array($sqlProject, [$projectId]);

        $sqlTheme       = "select * from findtalent_theme where id = ? limit 1";
        $dataTheme      = DB_global::cz_result_array($sqlTheme, [$themeId]);

        $sqlUser        = "select * from users where id = ? limit 1";
        $dataUser       = DB_global::cz_result_array($sqlUser, [$imdlId]);

        $toEmail        =   $dataUser['email'];

        $imageLogo      = $this->amazoneLink.'theme/'.$dataTheme['image_logo'];
        $subject        = $dataTheme['text_email_subject_approved_project'];

        $messageBody    =   str_replace('<project_title>', $dataProject['title'], $dataTheme['text_email_body_approved']);
        $messageBody    =   str_replace('<full_name>', $dataUser['full_name'], $messageBody);
        $messageBody    =   str_replace('<start_date>', $dataProject['start_date'], $messageBody);
        $messageBody    =   str_replace('<project_manager>', $dataProject['project_manager'], $messageBody);

        $bodyHtml           = "<html xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns:m='http://schemas.microsoft.com/office/2004/12/omml' xmlns='http://www.w3.org/TR/REC-html40'>

        <head>
            <META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=us-ascii'>
            <meta name=Generator content='Microsoft Word 15 (filtered medium)'>
            <![if !mso]><style>v\:* {behavior:url(#default#VML);}
        o\:* {behavior:url(#default#VML);}
        w\:* {behavior:url(#default#VML);}
        .shape {behavior:url(#default#VML);}
        </style><![endif]>
        <style>
        /* Font Definitions */

        @font-face {
            font-family: 'Cambria Math';
            panose-1: 2 4 5 3 5 4 6 3 2 4;
        }

        @font-face {
            font-family: Calibri;
            panose-1: 2 15 5 2 2 2 4 3 2 4;
        }
        /* Style Definitions */

        p.MsoNormal,
        li.MsoNormal,
        div.MsoNormal {
            margin: 0in;
            margin-bottom: .0001pt;
            font-size: 11.0pt;
            font-family: 'Calibri', sans-serif;
        }

        a:link,
        span.MsoHyperlink {
            mso-style-priority: 99;
            color: blue;
            text-decoration: underline;
        }

        a:visited,
        span.MsoHyperlinkFollowed {
            mso-style-priority: 99;
            color: purple;
            text-decoration: underline;
        }

        p.msonormal0,
        li.msonormal0,
        div.msonormal0 {
            mso-style-name: msonormal;
            mso-margin-top-alt: auto;
            margin-right: 0in;
            mso-margin-bottom-alt: auto;
            margin-left: 0in;
            font-size: 11.0pt;
            font-family: 'Calibri', sans-serif;
        }

        span.EmailStyle18 {
            mso-style-type: personal;
            font-family: 'Calibri', sans-serif;
            color: windowtext;
        }

        span.EmailStyle19 {
            mso-style-type: personal-reply;
            font-family: 'Calibri', sans-serif;
            color: windowtext;
        }

        .MsoChpDefault {
            mso-style-type: export-only;
            font-size: 10.0pt;
        }

        @page WordSection1 {
            size: 8.5in 11.0in;
            margin: 1.0in 1.0in 1.0in 1.0in;
        }

        div.WordSection1 {
            page: WordSection1;
        }
        hr.colored {
            border: 0;  
            height: 2px;
            background: #000;
        }
        </style>
        <![if gte mso 9]><xml>
        <o:shapedefaults v:ext='edit' spidmax='1028' />
        </xml><![endif]>
        <![if gte mso 9]><xml>
        <o:shapelayout v:ext='edit'>
        <o:idmap v:ext='edit' data='1' />
        </o:shapelayout></xml><![endif]>
        </head>

        <body lang=EN-US link=blue vlink=purple>
        <table class=MsoNormalTable border=0 cellspacing=3 cellpadding=0>
            <tr>
                <td style='width:33%'>
                </td>
                <td style='width:33%'>
                </td>
                <td style='width:33%;text-align:right;padding:0 20px'>
                    <a href='" . $this->appLink . "'><img src='".$imageLogo."'></a>
                </td>
            </tr>
            <tr style='margin-top:10px;margin-bottom:10px;'>
                <td colspan='3'>
                    <hr class='colored'/>
                </td>
            </tr>
            <tr>
                <td colspan='3' style='font-family:Calibri,Arial;font-size:18px;padding:0 30px;text-align:justify;'>
                    ".$messageBody."
                </td>
            </tr>
        </table>
        </body>
        ";
        $bodyText =  "Email Test\r\nThis email was sent through the
        Amazon SES SMTP interface using the PHPMailer class.";
        
       

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->setFrom($this->noReply, $this->fromName);
        $mail->Username   = $this->usernameEmail;    
        $mail->Password   = $this->passwordEmail;
        $mail->Host       = $this->hostEmail;
        $mail->Port       = $this->portEmail;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'tls';
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject    = $subject;
        $mail->Body       = $bodyHtml;
        $mail->AltBody    = $bodyText;
        $mail->Send();

        $array_log = array(
            'sender_name'   =>  $this->fromName,
            'subject'       =>  $subject,
            'email_to'      =>  $toEmail,
            'email_body'    =>  $bodyHtml,
            'flag_email'    =>  1,
            'date_email'    =>  DB_global::Global_CurrentDatetime(),
            'transaction_id'=>  $projectId);
        DB_global::InsertEmailLog($array_log);
        
    }
    public function sendEmailProjectRejected($themeId,$projectId,$imdlId){

        $sqlProject     = "select * from findtalent_project where id = ? limit 1";
        $dataProject    = DB_global::cz_result_array($sqlProject, [$projectId]);

        $sqlTheme       = "select * from findtalent_theme where id = ? limit 1";
        $dataTheme      = DB_global::cz_result_array($sqlTheme, [$themeId]);

        $sqlUser        = "select * from users where id = ? limit 1";
        $dataUser       = DB_global::cz_result_array($sqlUser, [$imdlId]);

        $toEmail        =   $dataUser['email'];

        $imageLogo      = $this->amazoneLink.'theme/'.$dataTheme['image_logo'];
        $subject        = $dataTheme['text_email_subject_reject_project'];

        $messageBody    =   str_replace('<project_title>', $dataProject['title'], $dataTheme['text_email_body_rejected']);
        $messageBody    =   str_replace('<full_name>', $dataUser['full_name'], $messageBody);
        $messageBody    =   str_replace('<project_manager>', $dataProject['project_manager'], $messageBody);

        $bodyHtml           = "<html xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns:m='http://schemas.microsoft.com/office/2004/12/omml' xmlns='http://www.w3.org/TR/REC-html40'>

        <head>
            <META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=us-ascii'>
            <meta name=Generator content='Microsoft Word 15 (filtered medium)'>
            <![if !mso]><style>v\:* {behavior:url(#default#VML);}
        o\:* {behavior:url(#default#VML);}
        w\:* {behavior:url(#default#VML);}
        .shape {behavior:url(#default#VML);}
        </style><![endif]>
        <style>
        /* Font Definitions */

        @font-face {
            font-family: 'Cambria Math';
            panose-1: 2 4 5 3 5 4 6 3 2 4;
        }

        @font-face {
            font-family: Calibri;
            panose-1: 2 15 5 2 2 2 4 3 2 4;
        }
        /* Style Definitions */

        p.MsoNormal,
        li.MsoNormal,
        div.MsoNormal {
            margin: 0in;
            margin-bottom: .0001pt;
            font-size: 11.0pt;
            font-family: 'Calibri', sans-serif;
        }

        a:link,
        span.MsoHyperlink {
            mso-style-priority: 99;
            color: blue;
            text-decoration: underline;
        }

        a:visited,
        span.MsoHyperlinkFollowed {
            mso-style-priority: 99;
            color: purple;
            text-decoration: underline;
        }

        p.msonormal0,
        li.msonormal0,
        div.msonormal0 {
            mso-style-name: msonormal;
            mso-margin-top-alt: auto;
            margin-right: 0in;
            mso-margin-bottom-alt: auto;
            margin-left: 0in;
            font-size: 11.0pt;
            font-family: 'Calibri', sans-serif;
        }

        span.EmailStyle18 {
            mso-style-type: personal;
            font-family: 'Calibri', sans-serif;
            color: windowtext;
        }

        span.EmailStyle19 {
            mso-style-type: personal-reply;
            font-family: 'Calibri', sans-serif;
            color: windowtext;
        }

        .MsoChpDefault {
            mso-style-type: export-only;
            font-size: 10.0pt;
        }

        @page WordSection1 {
            size: 8.5in 11.0in;
            margin: 1.0in 1.0in 1.0in 1.0in;
        }

        div.WordSection1 {
            page: WordSection1;
        }
        hr.colored {
            border: 0;  
            height: 2px;
            background: #000;
        }
        </style>
        <![if gte mso 9]><xml>
        <o:shapedefaults v:ext='edit' spidmax='1028' />
        </xml><![endif]>
        <![if gte mso 9]><xml>
        <o:shapelayout v:ext='edit'>
        <o:idmap v:ext='edit' data='1' />
        </o:shapelayout></xml><![endif]>
        </head>

        <body lang=EN-US link=blue vlink=purple>
        <table class=MsoNormalTable border=0 cellspacing=3 cellpadding=0>
            <tr>
                <td style='width:33%'>
                </td>
                <td style='width:33%'>
                </td>
                <td style='width:33%;text-align:right;padding:0 20px'>
                    <a href='" . $this->appLink . "'><img src='".$imageLogo."'></a>
                </td>
            </tr>
            <tr style='margin-top:10px;margin-bottom:10px;'>
                <td colspan='3'>
                <hr class='colored'/>
                </td>
            </tr>
            <tr>
                <td colspan='3' style='font-family:Calibri,Arial;font-size:18px;padding:0 30px;text-align:justify;'>
                    ".$messageBody."
                </td>
            </tr>
        </table>
        </body>
        ";
        $bodyText =  "Email Test\r\nThis email was sent through the
        Amazon SES SMTP interface using the PHPMailer class.";
        
       

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->setFrom($this->noReply, $this->fromName);
        $mail->Username   = $this->usernameEmail;    
        $mail->Password   = $this->passwordEmail;
        $mail->Host       = $this->hostEmail;
        $mail->Port       = $this->portEmail;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = 'tls';
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject    = $subject;
        $mail->Body       = $bodyHtml;
        $mail->AltBody    = $bodyText;
        $mail->Send();

        $array_log = array(
            'sender_name'   =>  $this->fromName,
            'subject'       =>  $subject,
            'email_to'      =>  $toEmail,
            'email_body'    =>  $bodyHtml,
            'flag_email'    =>  1,
            'date_email'    =>  DB_global::Global_CurrentDatetime(),
            'transaction_id'=>  $projectId);
        DB_global::InsertEmailLog($array_log);
        
    }
}
