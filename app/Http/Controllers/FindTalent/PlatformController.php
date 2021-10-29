<?php

namespace App\Http\Controllers\FindTalent;

use DB_global;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\FindTalent\User;

class PlatformController extends Controller
{
    protected $folder_name = 'findtalent/platform';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

       $sql = "select * from findtalent_platform_hdr order by date_modified desc, status_active desc";

       $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
       $param = [];
       if ($category != "COUNT" && $export == false)
       {
           $sql = $sql . " LIMIT  :offset,:limit ";

           $param = array(
            'limit'=>$limit,
            'offset'=>$offset,
           );
       }

       try {
        //code...
            #print $sql;
            $data = DB_global::cz_result_set($sql,$param,false,$category);

            return response()->json([
                'data' => $data,
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

   public function SelectData(Request $request)
   {
       $id      = $request->input('md5ID');
       $sql     = "select * from findtalent_platform_hdr where md5(id) = :id limit 1";
       $sqlDtl1 = "select * from findtalent_platform_dtl_1 where md5(platform_id) = :id";
       $sqlDtl2 = "select * from findtalent_platform_dtl_2 where md5(platform_id) = :id";
       $sqlDtl3 = "select a.id, a.account, a.name from users a left join findtalent_platform_dtl_4 b on b.user_id = a.id where md5(b.platform_id) = :id and group_id = 1";
       $sqlDtl4 = "select a.id, a.account, a.name from users a
       left join findtalent_platform_dtl_3 b on b.imdl_id = a.id where md5(b.platform_id) = :id";
       $sqlDtl5 = "select a.id, a.account, a.name from users a left join findtalent_platform_dtl_4 b on b.user_id = a.id where group_id = 2";

       $param       = [];
       $paramForId  =   array(
                            'id'=>$id
                        );
       try {
        //code...
            $data       = DB_global::cz_result_array($sql,$paramForId);
            $dataDtl1   = DB_global::cz_result_set($sqlDtl1,$paramForId);
            $dataDtl2   = DB_global::cz_result_set($sqlDtl2,$paramForId);
            $dataDtl3   = DB_global::cz_result_set($sqlDtl3,$paramForId);
            $dataDtl4   = DB_global::cz_result_set($sqlDtl4,$paramForId);
            $dataDtl5   = DB_global::cz_result_set($sqlDtl5,$param);

            return response()->json([
                'data' => $data,
                'data2' => $dataDtl1,
                'data3' => $dataDtl2,
                'data4' => $dataDtl3,
                'data5' => $dataDtl4,
                'data6' => $dataDtl5,
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

   public function InsertData(Request $request)
   {
        $_arrayData = $request->input();
        $file       = $request->file('platform_image');
        $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName   = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

        $country        = $request->input('country');
        $directorate    = $request->input('function');
        // $admin = $request->input('admin');
        $adhoc          = $request->input('adhoc');
        $user_modified  = $request->input('user_created');

        $_arrayData = $request->except('user_account','energy_point','platform_image','country','function','admin','adhoc');
        $_arrayData = array_merge($_arrayData,
        array(
            'platform_image' => $fileName,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));

        $arrCountry = json_decode($country);
        $arrDirectorate = json_decode($directorate);
        // $arrAdmin = json_decode($admin);
        $arrAdhoc = json_decode($adhoc);
        

        try {
            /* insert to findtalent_platform_hdr and get id */
            $platform_id = DB_global::cz_insert('findtalent_platform_hdr', $_arrayData, true);

            /* loop array then insert findtalent_platform_dtl_1 */
            if(count($arrCountry) > 0){
                foreach($arrCountry as $countryDtl){
                    $_arrayDtl1 = array(
                        'platform_id'=>$platform_id,
                        'country'=>$countryDtl->value,
                        'flag_active'=>1,
                        'user_modified'=>$user_modified,
                        'date_modified'=> DB_global::Global_CurrentDatetime()
                    );
                    $dtl1 = DB_global::cz_insert('findtalent_platform_dtl_1', $_arrayDtl1, true);
                }
            }

            if(count($arrDirectorate) > 0){
                foreach($arrDirectorate as $directorateDtl){
                    $_arrayDtl2 = array(
                        'platform_id'=>$platform_id,
                        'directorate'=>$directorateDtl->value,
                        'flag_active'=>1,
                        'user_modified'=>$user_modified,
                        'date_modified'=> DB_global::Global_CurrentDatetime()
                    );
                    $dtl1 = DB_global::cz_insert('findtalent_platform_dtl_2', $_arrayDtl2, true);
                }
            }

            // if(count($arrAdmin) > 0){
            //     foreach($arrAdmin as $adminDtl){
            //         $_arrayDtl3 = array(
            //             'group_id'=>1,
            //             'user_id'=>$adminDtl->value,
            //             'user_created'=>$user_modified,
            //             'date_created'=> DB_global::Global_CurrentDatetime(),
            //             'user_modified'=>$user_modified,
            //             'date_modified'=> DB_global::Global_CurrentDatetime(),
            //             'platform_id'=>$platform_id,
            //             'flag_active'=>1
            //         );
            //         $dtl1 = DB_global::cz_insert('findtalent_platform_dtl_4', $_arrayDtl3, true);
            //     }
            // }

            if(count($arrAdhoc) > 0){
                foreach($arrAdhoc as $adhocDtl){
                    $_arrayDtl4 = array(
                        'platform_id'=>$platform_id,
                        'imdl_id'=>$adhocDtl->value,
                        'flag_active'=>1,
                        'user_modified'=>$user_modified,
                        'date_modified'=> DB_global::Global_CurrentDatetime()
                    );
                    $dtl1 = DB_global::cz_insert('findtalent_platform_dtl_3', $_arrayDtl4, true);
                }
            }

            $sqlInsertTheme = "
            
            INSERT INTO `findtalent_theme` ( `theme_name`, `platform_id`, `lang`, `image_logo`, `image_home`, `image_report`, `image_admin`, `image_menu_available`, `top_menu_color`, `top_menu_background`, `top_menu_border`, `footer_background`, `text_footer_color`, `text_footer`, `background_btn_submit`, `text_color_btn_submit`, `text_home_page`, `text_save_projects`, `text_applied_projects`, `background_profile`, `user_modified`, `date_modified`, `status_active`, `default_flag`, `is_deleted`, `text_registration_closed_by`, `text_project_manager`, `text_project_duration`, `text_project_start_date`, `text_avg_time_needed`, `text_button_save_project`, `text_button_applied_project`, `text_button_submit_applied_project`, `background_top_button`, `text_color_top_button`, `text_email_subject_publish_project`, `text_email_subject_approved_project`, `text_email_subject_reject_project`, `text_saved_project`, `text_applied_project`, `text_email_body_publish`, `text_email_body_rejected`, `text_email_body_approved`) VALUES
            ( 'Theme One', '".$platform_id."', 'eng', 'asantoso9_finallogo.png', 'asantoso9_black_i_home.png', 'asantoso9_black_i_report.png', 'asantoso9_black_i_Admin.png', 'asantoso9_menu.png', '#ffffff', 'linear-gradient(to right, #d31d85, #f58220, #d31d85)', 'linear-gradient(to right, #ffffff, #fbab0c, #ffffff)', 'linear-gradient(to right, #d31d85, #f58220, #d31d85)', '#ffffff', 'Philips Morris © 2021. All rights reserved.', 'linear-gradient(to right, #fba815, #fba815, #fba815)', '#ffffff',  'Project Opportunities<br/> Take the Action: Pick Your Team!', 'Save Projects', 'Applied Projects', 's', '1000494053', '2021-08-25 02:00:09', 1, 1, NULL, 'Registration Closed By', 'Project Manager', 'Project Duration', 'Project Start Date', 'Avg. Time Needed', 'Save', 'Apply Now', 'Submit', '#fba815', '#ffffff', 'New Project is Coming!', 'Congratulations!', 'Thank You!', 'You’ve saved this project.', 'Thank you for Applying this Project. We will contact you as soon as possbile :)', '<h2><project_title></h2>\n<br/>\nGreetings <full_name>,	\n<br/><br/>\nDo you want to grow your capabilities beyond what you thought? If the answer is yes, then we would like to hear from you 	\n<br/>\nWe are currently looking for a talent/talented people to join us for <b><project_title></b> Project. Be a part of our project and make a difference in the company!									\n<br/><br/>\n<description>\n<br/><br/><br/>\nStart Date\n<br/>\n<b><start_date></b>\n<br/>\n<br/>\nProject Period\n<br/>\n<b><duration_length> <duration_period_type></b>\n<br/>\n<br/>\nAvg. Time Needed\n<br/>\n<b><avg_time_needed></b>\n<br/>\n<br/>\n*We encouraged you to notice your supervisor before applying to this project		\n<br/><br/>\n<span style=\'font-size:18px\'>Registration will be closed at <registation_closed_by></span>		\n<br/><br/>					\n<span style=\'font-weight:bold\'>For Further Information you can contact,</span> 		\n<br/>\n<project_manager>', '<h2>Thank You!</h2>\n<br>\nDear <full_name>,	\n<br><br>\nOur greatest gratitude for your enthusiasm and the time you\'ve invested in applying to <b><project_title></b>. After making difficult choices between many high-caliber candidates however we regret to inform you that we will not be pursuing your candidacy for this project . We hope you\'ll keep us in mind and we encourage you to apply for future openings.\n<br><br>\nOnce again thank you very much for your interest, we hope that we can collaborate in the future.\n\n<br><br>\nWarm Regards,<br>\n<b><project_manager></b>\n<br><br><br>', '<h1>Congratulations!</h1>\n<br>\nDear <full_name>,	\n<br><br>\nWe are more than pleased to inform that you have been selected to be a part of the project <project_title>. <project_title> will start working at <start_date>, should you have any further questions, you can contact <project_manager>.    \n<br><br>\nWe are looking forward to a succesful teamwork in this project.\n<br><br>\nThank You!\n<br><br>\nWarm Regards,<br/>\n<b><project_manager></b>\n<br><br>')

            ";
            $insertTheme = DB_global::cz_execute_query($sqlInsertTheme, [], true);

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

    function ConvertToArray($longstring)
    {
        $longstring = str_replace("[","",$longstring);
        $longstring = str_replace("]","",$longstring);
        $longstring = str_replace('"',"",$longstring);
        $arrString = explode(",",$longstring);

        return $arrString;
    }

   public function UpdateData(Request $request)
    {
        $platform_id    = $request->input('id');
        $user_modified  = $request->input('user_modified');
        $_arrayData     = $request->except('user_account','platform_image','id','country','function','admin','adhoc');

        if($request->hasFile('platform_image'))
        {
            $file       = $request->file('platform_image');
            $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName   = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

            $_arrayData = array_merge($_arrayData,['platform_image' => $fileName]);
        }
        $_arrayData     = array_merge($_arrayData,['date_modified'=> DB_global::Global_CurrentDatetime()]);

        $country        = $request->input('country');
        $directorate    = $request->input('function');
        // $admin = $request->input('admin');
        $adhoc = $request->input('adhoc');

        $arrCountry     = json_decode($country);
        $arrDirectorate = json_decode($directorate);
        // $arrAdmin = json_decode($admin);
        $arrAdhoc       = json_decode($adhoc);

        try {
            $data = DB_global::cz_update('findtalent_platform_hdr','id',$platform_id,$_arrayData);
            //echo $dataJoss = $data;
            if(count($arrCountry) > 0){
                /* update flag_active to 0 */
                $this->UpdateFlag('findtalent_platform_dtl_1', $platform_id);
                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('findtalent_platform_dtl_1', $paramDel);
                foreach($arrCountry as $countryDtl){
                    $sql = "SELECT id FROM findtalent_platform_dtl_1 WHERE platform_id=:platform_id and country=:country";
                    $param = array(
                        'platform_id' => $platform_id,
                        'country' => $countryDtl->value,
                    );
                    $checkCountry =  DB_global::bool_CheckRowExist($sql, $param);
                    if(!empty($checkCountry)){
                        /* kalo ada, update flag ke 1*/
                        DB_global::cz_update('findtalent_platform_dtl_1', 'flag_active', 1, $param);
                    } else {
                        /* kalo ga ada insert country */
                        $_arrayDtl1 = array(
                            'platform_id'=>$platform_id,
                            'country'=>$countryDtl->value,
                            'flag_active'=>1,
                            'user_modified'=>$user_modified,
                            'date_modified'=> DB_global::Global_CurrentDatetime()
                        );
                        $dtl1 = DB_global::cz_insert('findtalent_platform_dtl_1', $_arrayDtl1, true);
                    }
                }

            }

            if(count($arrCountry) == 0){
                $this->UpdateFlag('findtalent_platform_dtl_1', $platform_id);

                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('findtalent_platform_dtl_1', $paramDel);
            }

            if(count($arrDirectorate) > 0){
                /* update flag_active to 0 */
                $this->UpdateFlag('findtalent_platform_dtl_2', $platform_id);
                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('findtalent_platform_dtl_2', $paramDel);
                foreach($arrDirectorate as $directorateDtl){
                    $sql = "SELECT id FROM findtalent_platform_dtl_2 WHERE platform_id=:platform_id and 'directorate'=:directorate";
                    $param = array(
                        'platform_id' => $platform_id,
                        'directorate' => $directorateDtl->value,
                    );
                    $checkFunction =  DB_global::bool_CheckRowExist($sql, $param);
                    if(!empty($checkFunction)){
                        /* kalo ada, update flag ke 1*/
                        DB_global::cz_update('findtalent_platform_dtl_2', 'flag_active', 1, $param);
                    } else {
                        $_arrayDtl2 = array(
                            'platform_id' => $platform_id,
                            'directorate' => $directorateDtl->value,
                            'flag_active' => 1,
                            'user_modified' => $user_modified,
                            'date_modified' => DB_global::Global_CurrentDatetime()
                        );
                        $dtl2 = DB_global::cz_insert('findtalent_platform_dtl_2', $_arrayDtl2, true);
                    }
                }

            }

            if(count($arrDirectorate) == 0){
                $this->UpdateFlag('findtalent_platform_dtl_2', $platform_id);

                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('findtalent_platform_dtl_2', $paramDel);
            }

            // if(count($arrAdmin) > 0){
            //     $this->UpdateFlag('findtalent_platform_dtl_4', $platform_id);
            //     $paramDel = array(
            //         'platform_id' => $platform_id,
            //         'flag_active' => 0
            //     );
            //     DB_global::cz_delete_array('findtalent_platform_dtl_4', $paramDel);
            //     foreach($arrAdmin as $adminDtl){
            //         $sql = "SELECT id FROM findtalent_platform_dtl_4 WHERE platform_id=:platform_id and user_id=:user_id";
            //         $param = array(
            //             'platform_id' => $platform_id,
            //             'user_id' => $adminDtl->value,
            //         );
            //         $checkUserId =  DB_global::bool_CheckRowExist($sql, $param);
            //         if(!empty($checkUserId)){
            //             /* kalo ada, update flag ke 1*/
            //             DB_global::cz_update('findtalent_platform_dtl_4', 'flag_active', 1, $param);
            //         } else {
            //             $_arrayDtl3 = array(
            //                 'group_id'=>1,
            //                 'user_id'=>$adminDtl->value,
            //                 'user_created'=>$user_modified,
            //                 'date_created'=> DB_global::Global_CurrentDatetime(),
            //                 'user_modified'=>$user_modified,
            //                 'date_modified'=> DB_global::Global_CurrentDatetime(),
            //                 'platform_id'=>$platform_id,
            //                 'flag_active'=>1
            //             );
            //             $dtl3 = DB_global::cz_insert('findtalent_platform_dtl_4', $_arrayDtl3, true);
            //         }
            //     }

            // }

            // if(count($arrAdmin) == 0){
            //     $this->UpdateFlag('findtalent_platform_dtl_4', $platform_id);

            //     $paramDel = array(
            //         'platform_id' => $platform_id,
            //         'flag_active' => 0
            //     );
            //     DB_global::cz_delete_array('findtalent_platform_dtl_4', $paramDel);
            // }

            if(count($arrAdhoc) > 0){
                $this->UpdateFlag('findtalent_platform_dtl_3', $platform_id);
                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('findtalent_platform_dtl_3', $paramDel);
                foreach($arrAdhoc as $adhocDtl){
                    $sql = "SELECT id FROM findtalent_platform_dtl_3 WHERE platform_id=:platform_id and imdl_id=:imdl_id";
                    $param = array(
                        'platform_id' => $platform_id,
                        'imdl_id' => $adhocDtl->value,
                    );
                    $checkUserId =  DB_global::bool_CheckRowExist($sql, $param);
                    if(!empty($checkUserId)){
                        /* kalo ada, update flag ke 1*/
                        DB_global::cz_update('findtalent_platform_dtl_3', 'flag_active', 1, $param);
                    } else {
                        $_arrayDtl4 = array(
                            'platform_id'=>$platform_id,
                            'imdl_id'=>$adhocDtl->value,
                            'flag_active'=>1,
                            'user_modified'=>$user_modified,
                            'date_modified'=> DB_global::Global_CurrentDatetime()
                        );
                        $dtl3 = DB_global::cz_insert('findtalent_platform_dtl_3', $_arrayDtl4, true);
                    }
                }

            }

            if(count($arrAdhoc) == 0){
                $this->UpdateFlag('findtalent_platform_dtl_3', $platform_id);

                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('findtalent_platform_dtl_3', $paramDel);
            }

            return response()->json([
                'data' => $data,
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

    function UpdateFlag($tablename, $platform_id)
    {
        DB::enableQueryLog();
        DB::table($tablename)
                ->where('platform_id', $platform_id)
                ->update(['flag_active' => 0 ]);
    }

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $dtl1 = DB_global::cz_delete('findtalent_platform_dtl_1','platform_id',$id);
            $dtl2 = DB_global::cz_delete('findtalent_platform_dtl_2','platform_id',$id);
            $dtl3 = DB_global::cz_delete('findtalent_platform_dtl_3','platform_id',$id);
            $hdr = DB_global::cz_delete('findtalent_platform_hdr','id',$id);

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

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            //code...
            $data = DB_global::bool_ValidateDataOnTableById_md5('findtalent_platform_hdr',$id);
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
        $name = $request->input('name');
        $sql = "select * from findtalent_platform_hdr where name = ?";
        try {
            //code...
            $data = DB_global::bool_CheckRowExist($sql,[$name]);
            return response()->json([
                'data' => $data,
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

    public function GetAllCountry()
    {
        try {
            $country = new DB_global;
            $data = $country->lang();
            return response()->json([
                'data' => $data,
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

    public function GetAllFunction()
    {
        try {
            $function = new DB_global;
            $data = $function->function();
            return response()->json([
                'data' => $data,
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

    public function GetAllEmployee(Request $request)
    {
        ini_set('memory_limit','512M');
        $country = $request->input('country');
        $directorate = $request->input('directorate');

        $sql = "select id, account, name from users
            where status_active = 1
            and directorate is not null
            and (
                (country in (:country))
                or
                (directorate in (:directorate))
                )
            order by id";

        try {
            // $data = User::select('id','account','name')->where('status_active',1)->whereNotNull('directorate')->whereIn('country', $country)->orWhereIn('directorate',$directorate)->orderBy('id', 'asc')->get();

            $data2 = User::select('id','account','name')
            ->where('status_active',1)
            ->whereNotIn('country', $country)
            ->orWhereNotIn('directorate',$directorate)
            ->orderBy('id', 'asc')->get();

            return response()->json([
                // 'data' => $data,
                'data2' => $data2,
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

    public function GetAllEmployeeByCountry(Request $request)
    {
        ini_set('memory_limit','512M');
        ini_set('memory_limit','512M');
        $country = $request->input('country');

        $sql = "
            select 
                id, account, name 
            from 
                users
            where 
                status_active = 1
                and directorate is not null
                and country = :country
            order by id";

        try {
            $data = DB_global::cz_result_set($sql,[$country]);

            return response()->json([
                // 'data' => $data,
                'data2' => $data,
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

    public function GetPlatformAccess(Request $request)
    {
        $country = $request->input('country');
        $directorate = $request->input('directorate');
        $user_id = $request->input('user_id');

        $sql = "SELECT DISTINCT h.* FROM findtalent_platform_hdr h
                LEFT JOIN findtalent_platform_dtl_1 d1 on d1.platform_id = h.id
                LEFT JOIN findtalent_platform_dtl_2 d2 on d2.platform_id = h.id
                LEFT JOIN findtalent_platform_dtl_3 d3 on d3.platform_id = h.id
                WHERE h.status_active = 1
                AND (
                    (d1.country = :country1 AND d2.directorate = :directorate1)
                OR  (d1.country = :country2 AND d2.directorate is null)
                OR  (d1.country is null AND d2.directorate = :directorate2)
                OR  (d3.imdl_id = :userid)
                )";
        $param = ['country1'=>$country,
                'country2'=>$country,
                'directorate1'=>$directorate,
                'directorate2'=>$directorate,
                'userid'=>$user_id
        ];

        // $sql = "SELECT h.* FROM findtalent_platform_hdr h
        // LEFT JOIN findtalent_platform_dtl_1 d1 on d1.platform_id = h.id
        // LEFT JOIN findtalent_platform_dtl_2 d2 on d2.platform_id = h.id
        // WHERE h.status_active = 1
        // AND (
        //     d1.country = :country1 OR d2.directorate = :directorate1
        // )";
        // $param = ['country1'=>$country,
        //         'directorate1'=>$directorate,
        // ];
        try {
            $data = DB_global::cz_result_set($sql, $param, false);
            $totalData = count($data);
            return response()->json([
                'data' => $data,
                'data2' => $totalData,
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

    public function getRoleAdmin(Request $request){
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');

        try {
            $sql = "
            select 
                case 
                    when group_id = 1 then 'admin' 
                    when group_id = 2 then 'super admin' 
                else '' 
                
                end as role 
            from 
                findtalent_platform_dtl_4 
            where 
                user_id = :user_id 
                and platform_id = :platform_id limit 1";
            $param = [
                'user_id'=>$user_id,
                'platform_id'=>$platform_id
            ];
            $data = DB_global::cz_result_set($sql, $param, false);
            return response()->json([
                'data' => $data,
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

}
