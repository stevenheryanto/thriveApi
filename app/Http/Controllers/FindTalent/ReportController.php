<?php

namespace App\Http\Controllers\FindTalent;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\FindTalent\findtalent_project;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ActivityLogExport;

class ReportController extends Controller
{
    
    public function ListDataUserProject(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');

        $platform_id    = $request->input('platform_id');

        $param = [];


         $sql = "
            select 
                findtalent_project.title,
                findtalent_project.user_function,
                findtalent_project.location,
                findtalent_project.id,
                findtalent_project_user.status_project,
                findtalent_project_user.date_applied,
                findtalent_project_user.id as project_user_id,
                users.account,
                users.full_name
            from 
                findtalent_project,
                findtalent_project_user,
                users
            where 
                findtalent_project.platform_id = :platform_id 
                and findtalent_project_user.project_id = findtalent_project.id      
                and findtalent_project_user.status_project != 'Saved as Draft'
                and findtalent_project.status_active = 1
                and findtalent_project_user.status_active = 1
                and users.id = findtalent_project_user.imdl_id_applied

            order by 
                findtalent_project_user.date_applied desc
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
        $id     = $request->input('md5ID');
		$sql    = "
            select 
                findtalent_project.*,
                findtalent_project_user.status_project,
                findtalent_project_user.date_applied,
                findtalent_project_user.id as project_user_id,
                users.account,
                users.full_name
            from 
                findtalent_project,
                findtalent_project_user,
                users
            where 
                md5(findtalent_project_user.id) = ?
                and findtalent_project_user.project_id = findtalent_project.id      
                and findtalent_project_user.status_project != 'Saved as Draft'
                and findtalent_project.status_active = 1
                and findtalent_project_user.status_active = 1
                and users.id = findtalent_project_user.imdl_id_applied
            limit 1
            ";
        
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

    


    public function ListDataReportSummary(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $str_where = ' ';
        if(!empty($startDate) && !empty($endDate))
        {
            $str_where =  $str_where." and date(x.start_date) between :startDate and :endDate ";
        }

        $platform_id    = $request->input('platform_id');

        $param = [];


         $sql = "
            SELECT 
                x.title,x.start_date,x.id,x.status_active,
                count(y.id) as total_applicant,
                x.date_created,
                x.registation_closed_by
            FROM
                findtalent_project x 
                left join findtalent_project_user y 
                    on x.id = y.project_id 
                    and y.is_deleted = 0
            WHERE
                x.is_deleted = 0 
                and x.status_active in (0,1) 
                and x.platform_id = :platform_id
                $str_where
            GROUP BY 
                x.title,x.start_date,x.id,x.status_active
            ORDER BY 
                x.id DESC
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

           if(!empty($startDate) && !empty($endDate))
            {
                $param = array_merge($param, array(
                    'startDate'=> $startDate,
                    'endDate' => $endDate
                ));
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
    public function FormExportReportSummary(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $module_name = "findTalent";
        $dateStamp =  date('Ymdhms');
        $startDate      = $request->input('startDate');
        $endDate        = $request->input('endDate');
        $platform_id    = $request->input('platform_id');

        if(!empty($startDate) && !empty($endDate))
        {
            $plat_name = DB_global::cz_select('
                SELECT 
                    x.title,x.start_date,x.id,x.status_active,
                    count(y.id) as total_applicant,
                    x.date_created,
                    x.registation_closed_by
                FROM
                    findtalent_project x 
                    left join findtalent_project_user y 
                        on x.id = y.project_id 
                        and y.is_deleted = 0
                WHERE
                    x.is_deleted = 0 
                    and x.status_active in (0,1) 
                    and x.platform_id = :platform_id
                    and date(x.start_date) between :startDate and :endDate
                    $str_where
                GROUP BY 
                    x.title,x.start_date,x.id,x.status_active
                ORDER BY 
                    x.id DESC
            
            ', [$platform_id,$startDate,$endDate], 'name');
        }
        else{
            $plat_name = DB_global::cz_select('
                SELECT 
                    x.title,x.start_date,x.id,x.status_active,
                    count(y.id) as total_applicant,
                    x.date_created,
                    x.registation_closed_by
                FROM
                    findtalent_project x 
                    left join findtalent_project_user y 
                        on x.id = y.project_id 
                        and y.is_deleted = 0
                WHERE
                    x.is_deleted = 0 
                    and x.status_active in (0,1) 
                    and x.platform_id = :platform_id
                    $str_where
                GROUP BY 
                    x.title,x.start_date,x.id,x.status_active
                ORDER BY 
                    x.id DESC
            
            ', [$platform_id], 'name');
        }

        $access_module = $module_name.' - '.$plat_name;
        try {
            $folder_name = $module_name.'/temp/';
            $excelName = 'findtalent_report_summary'.$dateStamp. '.xlsx';

            // Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $excelName);
            // $path = Storage::path($excelName);
            Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $folder_name.$excelName);
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            // return response()->download($path, $excelName, $headers)->deleteFileAfterSend(true);
            return Storage::get($path, 200, $headers);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListDataReportDetail(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $module_name = "findTalent";
        $startDate  = $request->input('startDate');
        $endDate    = $request->input('endDate');
        $str_where  = '';

        if(!empty($startDate) && !empty($endDate)) {
            $str_where  = $str_where." and date(c.start_date) between :startDate and :endDate ";
        }

        $platform_id    = $request->input('platform_id');

        $param          = [];


         $sql = "

            SELECT 
                a.id,a.question,a.answer,
                b.imdl_id_applied,b.status_project,
                c.title,c.user_function,c.location,
                d.name,d.account,d.email,d.full_name
            FROM
                findtalent_project_user_questionnaire a 
                left join findtalent_project_user b on a.project_user = b.id 
                left join findtalent_project c on b.project_id = c.id 
                left join users d on d.id = b.imdl_id_applied
            WHERE
                b.status_active = 1 
                AND c.is_deleted = 0 
                AND c.status_active = 1
                and c.platform_id = :platform_id
                $str_where
            ORDER BY 
                a.id DESC
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

           if(!empty($startDate) && !empty($endDate))
            {
                $param = array_merge($param, array(
                    'startDate'=> $startDate,
                    'endDate' => $endDate
                ));
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

    public function FormExportReportDetail(Request $request)
    {
        $where      = $request->input('str_where');
        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $module_name = "findTalent";
        $dateStamp =  date('Ymdhms');
        
        $startDate      = $request->input('startDate');
        $endDate        = $request->input('endDate');
        $platform_id    = $request->input('platform_id');

        if(!empty($startDate) && !empty($endDate))
        {
            $plat_name = DB_global::cz_select('
            SELECT 
                a.id,a.question,a.answer,
                b.imdl_id_applied,b.status_project,
                c.title,c.user_function,c.location,
                d.name,d.account,d.email,d.full_name
            FROM
                findtalent_project_user_questionnaire a 
                left join findtalent_project_user b on a.project_user = b.id 
                left join findtalent_project c on b.project_id = c.id 
                left join users d on d.id = b.imdl_id_applied
            WHERE
                b.status_active = 1 
                AND c.is_deleted = 0 
                AND c.status_active = 1
                and c.platform_id = :platform_id
                and date(c.start_date) between :startDate and :endDate
                
            ORDER BY 
                a.id DESC
            
            ', [$platform_id,$startDate,$endDate],'name');

            //DB_global::cz_result_array($sql, [$id]);
        }
        else{
            $plat_name = DB_global::cz_select('
            SELECT 
                a.id,a.question,a.answer,
                b.imdl_id_applied,b.status_project,
                c.title,c.user_function,c.location,
                d.name,d.account,d.email,d.full_name
            FROM
                findtalent_project_user_questionnaire a 
                left join findtalent_project_user b on a.project_user = b.id 
                left join findtalent_project c on b.project_id = c.id 
                left join users d on d.id = b.imdl_id_applied
            WHERE
                b.status_active = 1 
                AND c.is_deleted = 0 
                AND c.status_active = 1
                and c.platform_id = :platform_id
                
            ORDER BY 
                a.id DESC
            
            ', [$platform_id],'name');
        }

        $access_module = $module_name.' - '.$plat_name;
        try {
            $folder_name = $module_name.'/temp/';
            $excelName = 'report_project_detail_'.$dateStamp. '.xlsx';

            // Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $excelName);
            // $path = Storage::path($excelName);
            Excel::store(new ActivityLogExport($access_module, $startDate, $endDate), $folder_name.$excelName);
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            // return response()->download($path, $excelName, $headers)->deleteFileAfterSend(true);
            return Storage::get($path, 200, $headers);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}
