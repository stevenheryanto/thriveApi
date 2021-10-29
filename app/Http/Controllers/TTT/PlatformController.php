<?php

namespace App\Http\Controllers\TTT;

use DB_global;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\TTT\User;

class PlatformController extends Controller
{
    protected $folder_name = 'think/platform';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $sql = "SELECT * FROM timetothink_platform_hdr ORDER BY date_modified DESC, status_active DESC";
        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        $param = [];
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT :offset, :limit ";
            $param = array(
                'limit'=>$limit,
                'offset'=>$offset,
            );
        }
        try {
            #print $sql;
            $data = DB_global::cz_result_set($sql, $param, false, $category);

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
       $id = $request->input('md5ID');
       $sql = "select * from timetothink_platform_hdr where md5(id) = ? limit 1";
       $sqlDtl1 = "select * from timetothink_platform_dtl_1 where md5(platform_id) = ?";
       $sqlDtl2 = "select * from timetothink_platform_dtl_2 where md5(platform_id) = ?";
       $sqlDtl3 = "select a.id, a.account, a.name from users a left join timetothink_platform_dtl_4 b on b.user_id = a.id where md5(b.platform_id) = ? and group_id = 1";
       $sqlDtl4 = "select a.id, a.account, a.name from users a
       left join timetothink_platform_dtl_3 b on b.imdl_id = a.id where md5(b.platform_id) = ?";
       $sqlDtl5 = "select a.id, a.account, a.name from users a left join timetothink_platform_dtl_4 b on b.user_id = a.id where group_id = 2";

       try {
        //code...
            $data = DB_global::cz_result_array($sql,[$id]);
            $dataDtl1 = DB_global::cz_result_set($sqlDtl1,[$id]);
            $dataDtl2 = DB_global::cz_result_set($sqlDtl2,[$id]);
            $dataDtl3 = DB_global::cz_result_set($sqlDtl3,[$id]);
            $dataDtl4 = DB_global::cz_result_set($sqlDtl4,[$id]);
            $dataDtl5 = DB_global::cz_result_set($sqlDtl5);

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
        $file = $request->file('platform_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

        $country = $request->input('country');
        $directorate = $request->input('function');
        // $admin = $request->input('admin');
        $adhoc = $request->input('adhoc');
        $user_modified = $request->input('user_created');

        $_arrayData = $request->except('user_account','platform_file','country','function','admin','adhoc');
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
            /* insert to timetothink_platform_hdr and get id */
            $platform_id = DB_global::cz_insert('timetothink_platform_hdr', $_arrayData, true);

            /* loop array then insert timetothink_platform_dtl_1 */
            if(count($arrCountry) > 0){
                foreach($arrCountry as $countryDtl){
                    $_arrayDtl1 = array(
                        'platform_id'=>$platform_id,
                        'country'=>$countryDtl->value,
                        'flag_active'=>1,
                        'user_modified'=>$user_modified,
                        'date_modified'=> DB_global::Global_CurrentDatetime()
                    );
                    $dtl1 = DB_global::cz_insert('timetothink_platform_dtl_1', $_arrayDtl1, true);
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
                    $dtl1 = DB_global::cz_insert('timetothink_platform_dtl_2', $_arrayDtl2, true);
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
            //         $dtl1 = DB_global::cz_insert('timetothink_platform_dtl_4', $_arrayDtl3, true);
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
                    $dtl1 = DB_global::cz_insert('timetothink_platform_dtl_3', $_arrayDtl4, true);
                }
            }

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
        $platform_id = $request->input('id');
        $user_modified = $request->input('user_modified');
        $_arrayData = $request->except('user_account','platform_file','id','country','function','admin','adhoc');

        if($request->hasFile('platform_file'))
        {
            $file = $request->file('platform_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');

            $_arrayData = array_merge($_arrayData,['platform_image' => $fileName]);
        }
        $_arrayData = array_merge($_arrayData,['date_modified'=> DB_global::Global_CurrentDatetime()]);

        $country = $request->input('country');
        $directorate = $request->input('function');
        // $admin = $request->input('admin');
        $adhoc = $request->input('adhoc');

        $arrCountry = json_decode($country);
        $arrDirectorate = json_decode($directorate);
        // $arrAdmin = json_decode($admin);
        $arrAdhoc = json_decode($adhoc);

        try {
            $data = DB_global::cz_update('timetothink_platform_hdr','id',$platform_id,$_arrayData);

            if(count($arrCountry) > 0){
                /* update flag_active to 0 */
                $this->UpdateFlag('timetothink_platform_dtl_1', $platform_id);
                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('timetothink_platform_dtl_1', $paramDel);
                foreach($arrCountry as $countryDtl){
                    $sql = "SELECT id FROM timetothink_platform_dtl_1 WHERE platform_id=:platform_id and country=:country";
                    $param = array(
                        'platform_id' => $platform_id,
                        'country' => $countryDtl->value,
                    );
                    $checkCountry =  DB_global::bool_CheckRowExist($sql, $param);
                    if(!empty($checkCountry)){
                        /* kalo ada, update flag ke 1*/
                        DB_global::cz_update('timetothink_platform_dtl_1', 'flag_active', 1, $param);
                    } else {
                        /* kalo ga ada insert country */
                        $_arrayDtl1 = array(
                            'platform_id'=>$platform_id,
                            'country'=>$countryDtl->value,
                            'flag_active'=>1,
                            'user_modified'=>$user_modified,
                            'date_modified'=> DB_global::Global_CurrentDatetime()
                        );
                        $dtl1 = DB_global::cz_insert('timetothink_platform_dtl_1', $_arrayDtl1, true);
                    }
                }

            }

            if(count($arrCountry) == 0){
                $this->UpdateFlag('timetothink_platform_dtl_1', $platform_id);

                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('timetothink_platform_dtl_1', $paramDel);
            }

            if(count($arrDirectorate) > 0){
                /* update flag_active to 0 */
                $this->UpdateFlag('timetothink_platform_dtl_2', $platform_id);
                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('timetothink_platform_dtl_2', $paramDel);
                foreach($arrDirectorate as $directorateDtl){
                    $sql = "SELECT id FROM timetothink_platform_dtl_2 WHERE platform_id=:platform_id and 'directorate'=:directorate";
                    $param = array(
                        'platform_id' => $platform_id,
                        'directorate' => $directorateDtl->value,
                    );
                    $checkFunction =  DB_global::bool_CheckRowExist($sql, $param);
                    if(!empty($checkFunction)){
                        /* kalo ada, update flag ke 1*/
                        DB_global::cz_update('timetothink_platform_dtl_2', 'flag_active', 1, $param);
                    } else {
                        $_arrayDtl2 = array(
                            'platform_id' => $platform_id,
                            'directorate' => $directorateDtl->value,
                            'flag_active' => 1,
                            'user_modified' => $user_modified,
                            'date_modified' => DB_global::Global_CurrentDatetime()
                        );
                        $dtl2 = DB_global::cz_insert('timetothink_platform_dtl_2', $_arrayDtl2, true);
                    }
                }

            }

            if(count($arrDirectorate) == 0){
                $this->UpdateFlag('timetothink_platform_dtl_2', $platform_id);

                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('timetothink_platform_dtl_2', $paramDel);
            }

            // if(count($arrAdmin) > 0){
            //     $this->UpdateFlag('timetothink_platform_dtl_4', $platform_id);
            //     $paramDel = array(
            //         'platform_id' => $platform_id,
            //         'flag_active' => 0
            //     );
            //     DB_global::cz_delete_array('timetothink_platform_dtl_4', $paramDel);
            //     foreach($arrAdmin as $adminDtl){
            //         $sql = "SELECT id FROM timetothink_platform_dtl_4 WHERE platform_id=:platform_id and user_id=:user_id";
            //         $param = array(
            //             'platform_id' => $platform_id,
            //             'user_id' => $adminDtl->value,
            //         );
            //         $checkUserId =  DB_global::bool_CheckRowExist($sql, $param);
            //         if(!empty($checkUserId)){
            //             /* kalo ada, update flag ke 1*/
            //             DB_global::cz_update('timetothink_platform_dtl_4', 'flag_active', 1, $param);
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
            //             $dtl3 = DB_global::cz_insert('timetothink_platform_dtl_4', $_arrayDtl3, true);
            //         }
            //     }

            // }

            // if(count($arrAdmin) == 0){
            //     $this->UpdateFlag('timetothink_platform_dtl_4', $platform_id);

            //     $paramDel = array(
            //         'platform_id' => $platform_id,
            //         'flag_active' => 0
            //     );
            //     DB_global::cz_delete_array('timetothink_platform_dtl_4', $paramDel);
            // }

            if(count($arrAdhoc) > 0){
                $this->UpdateFlag('timetothink_platform_dtl_3', $platform_id);
                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('timetothink_platform_dtl_3', $paramDel);
                foreach($arrAdhoc as $adhocDtl){
                    $sql = "SELECT id FROM timetothink_platform_dtl_3 WHERE platform_id=:platform_id and imdl_id=:imdl_id";
                    $param = array(
                        'platform_id' => $platform_id,
                        'imdl_id' => $adhocDtl->value,
                    );
                    $checkUserId =  DB_global::bool_CheckRowExist($sql, $param);
                    if(!empty($checkUserId)){
                        /* kalo ada, update flag ke 1*/
                        DB_global::cz_update('timetothink_platform_dtl_3', 'flag_active', 1, $param);
                    } else {
                        $_arrayDtl4 = array(
                            'platform_id'=>$platform_id,
                            'imdl_id'=>$adhocDtl->value,
                            'flag_active'=>1,
                            'user_modified'=>$user_modified,
                            'date_modified'=> DB_global::Global_CurrentDatetime()
                        );
                        $dtl3 = DB_global::cz_insert('timetothink_platform_dtl_3', $_arrayDtl4, true);
                    }
                }

            }

            if(count($arrAdhoc) == 0){
                $this->UpdateFlag('timetothink_platform_dtl_3', $platform_id);

                $paramDel = array(
                    'platform_id' => $platform_id,
                    'flag_active' => 0
                );
                DB_global::cz_delete_array('timetothink_platform_dtl_3', $paramDel);
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
            $dtl1 = DB_global::cz_delete('timetothink_platform_dtl_1','platform_id',$id);
            $dtl2 = DB_global::cz_delete('timetothink_platform_dtl_2','platform_id',$id);
            $dtl3 = DB_global::cz_delete('timetothink_platform_dtl_3','platform_id',$id);
            $hdr = DB_global::cz_delete('timetothink_platform_hdr','id',$id);

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
            $data = DB_global::bool_ValidateDataOnTableById_md5('timetothink_platform_hdr',$id);
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
        $sql = "select * from timetothink_platform_hdr where name = ?";
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

    public function GetPlatformAccess(Request $request)
    {
        $country = $request->input('country');
        $directorate = $request->input('directorate');
        $user_id = $request->input('user_id');

        $sql = "SELECT DISTINCT h.* FROM timetothink_platform_hdr h
                LEFT JOIN timetothink_platform_dtl_1 d1 on d1.platform_id = h.id
                LEFT JOIN timetothink_platform_dtl_2 d2 on d2.platform_id = h.id
                LEFT JOIN timetothink_platform_dtl_3 d3 on d3.platform_id = h.id
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

        // $sql = "SELECT h.* FROM timetothink_platform_hdr h
        // LEFT JOIN timetothink_platform_dtl_1 d1 on d1.platform_id = h.id
        // LEFT JOIN timetothink_platform_dtl_2 d2 on d2.platform_id = h.id
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
            $sql = "select case when group_id = 1 then 'admin' else '' end as role from timetothink_platform_dtl_4 where user_id = :user_id and platform_id = :platform_id limit 1";
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
