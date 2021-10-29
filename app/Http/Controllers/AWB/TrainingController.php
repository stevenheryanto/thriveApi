<?php

namespace App\Http\Controllers\AWB;

use AwbGlobal;
use DB_global;
use App\Http\Controllers\Controller;
use App\Imports\AWB\TrainingImport;
use App\Imports\AWB\TrainingEmployeeImport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_training;
use App\Models\AWB\awb_training_schedule;
use App\Models\AWB\awb_training_user;
use Maatwebsite\Excel\Facades\Excel;

class TrainingController extends Controller
{
    protected $table_name = 'awb_training';

    public function ListData(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $filter_search = $request->input('filter_search');
        $active_only= $request->input('active_only'); //boolean. if false, get all data. if true, exclude deleted data;

        try {
            $query = awb_training::where('platform_id', '=', $platform_id)
                ->when(isset($active_only), function($query) use($active_only){
                    if($active_only){
                        $query->whereNull('date_deleted');
                    }
                })
                ->when(isset($filter_search), function($query) use($filter_search){
                    $query->where('name', 'like', '%'.$filter_search.'%');
                });
            if($category != "COUNT"){
                $data = $query->limit($limit)
                    ->offset($offset)
                    ->orderBy('date_created', 'desc')
                    ->orderBy('status_active', 'desc')
                    ->get();
            } else {
                $data = $query->count();
            }
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

    public function ListDataForFO(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $filter_search = $request->input('filter_search');
        $month = $request->input('month');
        $year = $request->input('year');
        try {
            //data training
            $query = DB::table('awb_training')
                ->select('awb_training.id as training_id',
                    'awb_training.name as training_name',
                    'awb_training_schedule.schedule_date')
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.status_active', '=', 1)
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training.date_deleted')
                ->when(isset($filter_search), function($query) use($filter_search){
                    $query->where('awb_training.name', 'like', '%'.$filter_search.'%');
                })
                ->when(isset($month, $year), function($query) use($month, $year){
                    $query->where('awb_training_schedule.schedule_date', 'like', $year.'-'.$month.'%');
                })
                ->groupBy('awb_training.id')
                ->orderBy('awb_training_schedule.schedule_date', 'desc');

            $sumData = $query->get()->count();

            if($category != "COUNT"){
                $data = $query->limit($limit)
                    ->offset($offset)
                    ->get();
            } else {
                $data = $query->count();
            }
            //end data training

            //data schedule training
            $scheduleList = DB::table('awb_training_schedule')->selectRaw("
                                awb_training_schedule.id,
                                awb_training_schedule.awb_training_id,
                                awb_training_schedule.schedule_start_time,
                                awb_training_schedule.schedule_end_time,
                                awb_training_schedule.schedule_date,
                                awb_training_schedule.registration_end_date,
                                DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                                DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                                DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                                DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                            // ->where('awb_training_schedule.awb_training_id', '=', $training_id)
                            ->orderBy('awb_training_schedule.schedule_date')
                            ->get();
            //end data schedule training

            return response()->json([
                'data' => $data,
                'countData'=>$sumData,
                'scheduleList' => $scheduleList,
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
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $_arrayData = $request->input();
        $_arrayData = array_merge($_arrayData,
            array(
                'user_created' => $user_id,
                'date_created'=> DB_global::Global_CurrentDatetime(),
                'user_modified' => $user_id,
                'date_modified' => DB_global::Global_CurrentDatetime()
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
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $id = $request->input('id');
        $all = $request->except('id');
        $all = array_merge($all, [
                'user_modified' => $user_id,
                'date_modified' => DB_global::Global_CurrentDatetime()
            ]);
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
        try {
            $data = awb_training::where(DB::RAW('md5(id)'), '=', $id)->first();
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
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        try {
            if(DB::table('awb_training_schedule')
                ->join('awb_training_user', 'awb_training_user.awb_training_schedule_id', '=', 'awb_training_schedule.id')
                ->join('awb_training', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.id', '=', $training_id)
                ->whereNotNull('awb_training_user.date_rsvp')
                ->exists()){
                    $data = false;
                    $message = 'Cannot be deleted';
            } else {
                awb_training_schedule::where('awb_training_id', '=', $training_id)
                    ->update([
                        'user_deleted' => $user_id,
                        'date_deleted' => DB_Global::Global_CurrentDatetime()
                    ]);
                awb_training::where('id', '=', $training_id)
                    ->update([
                        'user_deleted' => $user_id,
                        'date_deleted' => DB_Global::Global_CurrentDatetime()
                    ]);
                $data = true;
                $message = 'delete success';
            };
            return response()->json([
                'data' => $data,
                'message' => $message
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'delete failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    function ListSection(Request $request)
	{
        $platform_id = $request->input('platform_id');
		$sql
            =
            "
            SELECT
                *
			FROM
               ".$this->table_name."
			where
                status_active = 1
                and platform_id = :platform_id
			order by
                name
            ";
        $category   = "";
        $param      = array('platform_id'=>$platform_id);
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

    public function ListTrainingStatus(Request $request)
    {
        /* ListDataByTrainingEmployeeForFo needs status, limit, offset */
        /* cekCountStatus needs $category = 'COUNT' */
        $userData = AwbGlobal::getUserData($request->bearerToken());
        // $user_id = $userData->id;
        $user_id = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $status = $request->input('status');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $filter_search = $request->input('filter_search');
        $month = $request->input('month');
        $year = $request->input('year');
        $currentDateTime = $request->input('currentDateTime');
        try {
            $query = DB::table('awb_training_user')->selectRaw("awb_training_user.*,
                    awb_training.id as training_id,
                    awb_training.name as training_name,
                    awb_training_schedule.hyperlink_url,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                ->join('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
                ->join('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.status_active', '=', 1)
                ->whereNull('awb_training.date_deleted')
                ->whereNull('awb_training_schedule.date_deleted')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->where('awb_training_user.employee_id', '=', $user_id)
                ->when($status == 'registered', function ($query) use($currentDateTime){
                    $query->whereNull('awb_training_user.date_attended')
                    ->whereNotNull('awb_training_user.date_rsvp')
                    ->where('awb_training_schedule.schedule_end_time', '>', $currentDateTime);
                })
                ->when($status == 'noregistered', function ($query) use($currentDateTime) {
                    $query->whereNull('awb_training_user.date_rsvp')
                    ->where(DB::raw('(SELECT
                        schedule_end_time
                        FROM awb_training_schedule AWS
                        WHERE AWS.awb_training_id = awb_training.id
                        ORDER BY AWS.schedule_end_time desc
                        LIMIT 1)'), '>', $currentDateTime);
                })
                ->when($status == 'attended', function ($query) {
                    $query->whereNotNull('awb_training_user.date_rsvp')
                    ->whereNotNull('awb_training_user.date_attended');
                })
                ->when($status == 'noattended', function ($query) use($currentDateTime){
                    $query->whereNull('awb_training_user.date_attended')
                    ->where('awb_training_schedule.schedule_end_time', '<', $currentDateTime);
                })
                ->when(isset($filter_search), function($query) use($filter_search){
                    $query->where('awb_training.name', 'like', '%'.$filter_search.'%');
                })
                ->when(isset($month, $year), function($query) use($month, $year){
                    $query->where('awb_training_schedule.schedule_date', 'like', $year.'-'.$month.'%');
                });

            $sumData = $query->get()->count();

            if($category != "COUNT"){
                $data = $query->limit($limit)
                            ->offset($offset)
                            ->get();
            } else {
                $data = $query->count();
            }

            return response()->json([
                'data' => $data,
                'countData'=>$sumData,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function status(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        try {
            $cekUserInTraining = DB::table('awb_training_user')->selectRaw("awb_training_user.*,
                    awb_training.name as training_name,
                    awb_training_schedule.hyperlink_url,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                ->leftJoin('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
                ->leftJoin('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->where('awb_training.id', '=', $training_id)
                ->where('awb_training_user.employee_id', '=', $user_id)
                ->first();
            $cekScheduleTerakhir = DB::table('awb_training')->selectRaw("
                    awb_training.name as training_name,
                    awb_training.id as awb_training_id,
                    awb_training_schedule.hyperlink_url,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->where('awb_training.id', '=', $training_id)
                ->orderBy('awb_training_schedule.registration_end_date', 'desc')
                ->first();
            return response()->json([
                'data1' => $cekUserInTraining,
                'data2' => $cekScheduleTerakhir,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function scheduleList(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        // $training_id = $request->input('training_id');
        try {
            $data = DB::table('awb_training_schedule')->selectRaw("
                    awb_training_schedule.awb_training_id,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                // ->where('awb_training_schedule.awb_training_id', '=', $training_id)
                ->orderBy('awb_training_schedule.schedule_date')
                ->get();
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

    public function cekStatusAllTraining(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        // $training_id = $request->input('training_id');
        try {
            $cekUserSchedule = DB::table('awb_training_user')->selectRaw("
                    awb_training.name as training_name,
                    awb_training.id as training_id,
                    awb_training_schedule.id as awb_training_schedule_id,
                    awb_training_schedule.hyperlink_url,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    awb_training_user.employee_id,
                    awb_training_user.date_rsvp,
                    awb_training_user.date_attended")
                ->join('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
                ->join('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->where('awb_training_user.employee_id', '=', $user_id)
                // ->where('awb_training_schedule.awb_training_id', '= ', $training_id)
                ->get();
            $cekScheduleRegistrasiTerakhir = DB::table('awb_training_schedule')
                ->select('id', 'registration_end_date')
                // ->where('awb_training_id', '=', $training_id)
                ->orderBy('registration_end_date', 'desc')
                ->first();
            $cekTraining = DB::table('awb_training')
                ->select('id', 'name')
                // ->where('id', '=', $training_id)
                ->where('platform_id', '=', $platform_id)
                ->first();
            return response()->json([
                'data1' => $cekUserSchedule,
                'data2' => $cekScheduleRegistrasiTerakhir,
                'data3' => $cekTraining,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cekStatusTrainingUser(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        $schedule_id = $request->input('schedule_id');
        try {
            $cekUserSchedule = DB::table('awb_training_user')->selectRaw("awb_training_user.*,
                    awb_training.name as training_name,
                    awb_training_schedule.hyperlink_url,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                ->leftJoin('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
                ->leftJoin('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->where('awb_training.id', '=', $training_id)
                ->where('awb_training_user.employee_id', '=', $user_id)
                ->where('awb_training_schedule.id', '=', $schedule_id)
                ->first();
            $cekScheduleRegistrasiTerakhir = DB::table('awb_training_schedule')
                ->select('id', 'registration_end_date')
                ->where('awb_training_id', '=', $training_id)
                ->orderBy('registration_end_date', 'desc')
                ->first();
            return response()->json([
                'data1' => $cekUserSchedule,
                'data2' => $cekScheduleRegistrasiTerakhir,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cekLink(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        $schedule_id = $request->input('schedule_id');
        try {
            $cekUserSchedule = DB::table('awb_training_user')->selectRaw("awb_training_user.*,
                    awb_training.name as training_name,
                    awb_training.id as training_id,
                    awb_training_schedule.id as awb_training_schedule_id,
                    awb_training_schedule.link_video_conference,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    awb_training_user.employee_id,
                    awb_training_user.date_rsvp,
                    awb_training_user.date_attended")
                ->join('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
                ->join('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->where('awb_training_user.employee_id', '=', $user_id)
                ->where('awb_training_schedule.awb_training_id', '= ', $training_id)
                ->where('awb_training_schedule.id', '=', $schedule_id)
                ->get();
            return response()->json([
                'data' => $cekUserSchedule,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cekStatusTrainingUserInTeam(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        try {
            $cekUserSchedule = DB::table('awb_training_user')->selectRaw("
                    awb_training.name as training_name,
                    awb_training.id as training_id,
                    awb_training_schedule.id as awb_training_schedule_id,
                    awb_training_schedule.link_video_conference,
                    awb_training_schedule.schedule_start_time,
                    awb_training_schedule.schedule_end_time,
                    awb_training_schedule.schedule_date,
                    awb_training_schedule.registration_end_date,
                    awb_training_user.id,
                    awb_training_user.employee_id,
                    awb_training_user.date_rsvp,
                    awb_training_user.date_attended")
                ->join('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
                ->join('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->where('awb_training_user.employee_id', '=', $user_id)
                ->where('awb_training_schedule.awb_training_id', '= ', $training_id)
                ->get();
            return response()->json([
                'data' => $cekUserSchedule,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ImportData(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        try {
            if($request->hasFile('training_file'))
            {
                $file = $request->file('training_file');
                $fileName = $userData->account. '_' .$file->getClientOriginalName();
                $fileName = DB_global::cleanFileName($fileName);
                Storage::putFileAs('learn/training', $file, $fileName, 'public');
            }
            Excel::import(new TrainingImport($userData->id, $platform_id, $fileName), $file);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteEmployee(Request $request)
	{
        $id = $request->input('id');
        try {
            $date_rsvp = awb_training_user::where('id', '=', $id)->value('date_rsvp');
            if($date_rsvp == ''){
                awb_training_user::where('id', '=', $id)->delete();
            }else{
                return response()->json([
                    'data' => false,
                    'message' => 'not deleted'
                ]);
            }
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function moveEmployee(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $schedule_id = $request->input('schedule_id');
        $id = $request->input('id');
        try {
            $date_rsvp = awb_training_user::where('id', '=', $id)->value('date_rsvp');
            if($date_rsvp == null){
                awb_training_user::where('id', '=', $id)->update([
                    'awb_training_schedule_id' => $schedule_id,
                    'user_updated' => $userData->id,
                    'date_updated' => DB_global::Global_CurrentDatetime(),
                    'date_rsvp' => null,
                    'date_attended' => null,
                    'status_email_active' => null,
                    'status_email_delete' => null,
                ]);
                $employee_id = awb_training_user::where('id', '=', $id)->value('employee_id');
                $arrayLog = [
                    'awb_training_id' => $id,
                    'awb_training_schedule_id' => $schedule_id,
                    'employee_id' => $employee_id,
                    'date_created' => DB_global::Global_CurrentDatetime(),
                ];
                DB_global::cz_insert('awb_training_log_schedule', $arrayLog);
                $data = true;
            } else {
                $data = false;
            }
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

    public function rsvp(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $id = $request->input('idDate');
        try {
            awb_training_user::where('id', '=', $id)->update([
                'date_rsvp' => DB_Global::Global_CurrentDatetime(),
                'user_updated' => $userData->id,
                'date_updated' => DB_Global::Global_CurrentDatetime()
            ]);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function hadir(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $id = $request->input('idDate');
        try {
            awb_training_user::where('id', '=', $id)->update([
                'date_attended' => DB_Global::Global_CurrentDatetime(),
                'user_updated' => $userData->id,
                'date_updated' => DB_Global::Global_CurrentDatetime()
            ]);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function newUserChange(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $schedule_id = $request->input('schedule_id');
        $training_id = $request->input('training_id');
        $action = $request->input('action');
        $platform_id = $request->input('platform_id');
        try {
            $totalUser = awb_training_user::where('awb_training_schedule_id', '=', $schedule_id)->count();
            $dataSchedule = awb_training_schedule::where('id', '=', $schedule_id)->first();
            if($totalUser){
                $totalUserNow = $totalUser;
            } else {
                $totalUserNow = 0;
            }
            if($totalUserNow < $dataSchedule->capacity){
                $cekData = DB::table('awb_training_user')
                    ->join('awb_training_schedule', 'awb_training_user.awb_training_schedule_id', '=', 'awb_training_schedule.id')
                    ->whereNull('awb_training_schedule.date_deleted')
                    ->where('awb_training_schedule.awb_training_id', '=', $dataSchedule->awb_training_id)
					->where('awb_training_user.employee_id', '=', $userData->id)
                    ->exists();
                if($cekData){
                    if($action == 'change'){
                        awb_training_user::where('id', '=', $training_id)->update([
                            'awb_training_schedule_id' => $schedule_id,
                            'date_rsvp' => null,
                            'date_attended' => null,
                            'status_email_active' =>  null,
                        ]);
                    } else {
                        awb_training_user::where('id', '=', $training_id)->update([
                            'awb_training_schedule_id' => $schedule_id,
                            'date_rsvp' => null,
                            'status_email_active' =>  null,
                        ]);
                    }
                } else {
                    if($action <> 'change'){

                        awb_training_user::insert([
                            'awb_training_schedule_id' => $schedule_id,
                            'employee_id' => $userData->id,
                            'user_created' => $userData->id,
                            'date_created' => DB_global::Global_CurrentDatetime(),
                        ]);
                    }
                }
                $arrayLog = [
                    'awb_training_id' => $dataSchedule->awb_training_id,
                    'awb_training_schedule_id' => $schedule_id,
                    'employee_id' => $userData->id,
                    'date_created' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id
                ];
                DB_global::cz_insert('awb_training_log_schedule', $arrayLog);
                $data = true;
            } else {
                $data = false;
            }
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

    public function changeStatusSupervisor(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $training_id = $request->input('training_id');
        $status = $request->input('status');
        try {
            $cekUserSchedule = DB::table('awb_training_schedule')
                ->select('awb_training_schedule.schedule_start_time', 'awb_training_schedule.registration_end_date')
                ->join('awb_training_user', 'awb_training_user.awb_training_schedule_id', '=', 'awb_training_schedule.id')
                ->where('awb_training_user.id', '=', $training_id)
                ->get();
            if($cekUserSchedule){
                if($status == 'attend'){
                    awb_training_user::where('id', '=', $training_id)->update([
                        'date_rsvp' => $cekUserSchedule[0]->registration_end_date,
                        'date_attended' => $cekUserSchedule[0]->schedule_start_time,
                        'user_updated' => $userData->id,
                        'date_updated' =>  DB_global::Global_CurrentDatetime(),
                    ]);
                } else {
                    awb_training_user::where('id', '=', $training_id)->update([
                        'date_attended' => null,
                        'user_updated' => $userData->id,
                        'date_updated' =>  DB_global::Global_CurrentDatetime(),
                    ]);
                }
                $data = true;
            } else {
                $data = false;
            }
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

    public function CountTrainingStatus(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        $currentDateTime = $request->input('currentDateTime');
        try {
            //   variable $query made by query builder cannot be used multiple times, it always append the next criteria
            //   so the developers decide to use native SQL query

            // $query = DB::table('awb_training_user')->selectRaw("count(*) as total")
            //     ->join('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
            //     ->join('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
            //     ->where('awb_training.status_active', '=', 1)
            //     ->whereNull('awb_training.date_deleted')
            //     ->whereNull('awb_training_schedule.date_deleted')
            //     ->where('awb_training.platform_id', '=', $platform_id)
            //     ->where('awb_training_user.employee_id', '=', $userData->id);

            // $query2 = $query;
            // $query3 = $query;
            // $query4 = $query;

            // $countRegistered = $query->whereNull('awb_training_user.date_attended')
            //         ->whereNotNull('awb_training_user.date_rsvp')
            //         ->where('awb_training_schedule.schedule_end_time', '>', DB_global::Global_CurrentDatetime())
            //         ->count();

            // $countNoRegistered = $query2->whereNull('awb_training_user.date_rsvp')
            //         ->where(DB::raw('(SELECT
            //             schedule_end_time
            //             FROM awb_training_schedule AWS
            //             WHERE AWS.awb_training_id = awb_training.id
            //             ORDER BY AWS.schedule_end_time desc
            //             LIMIT 1)'), '>', DB_global::Global_CurrentDatetime())
            //         ->count();

            // $countAttended = $query3->whereNotNull('awb_training_user.date_rsvp')
            //         ->whereNotNull('awb_training_user.date_attended')
            //         ->count();

            // $countNoAttended = $query4->whereNull('awb_training_user.date_attended')
            //         ->where('awb_training_schedule.schedule_end_time', '<', DB_global::Global_CurrentDatetime())
            //         ->count();

            $sql = "SELECT count(*) as total FROM
                        awb_training_user,
                        awb_training_schedule,
                        awb_training
                    where
                    awb_training.status_active = 1 AND
                    awb_training_schedule.id = awb_training_user.awb_training_schedule_id AND
                    awb_training.id = awb_training_schedule.awb_training_id AND
                    awb_training.date_deleted is null AND
                    awb_training_schedule.date_deleted is null AND
                    awb_training.platform_id = :platform_id AND
                    awb_training_user.employee_id = :id_user ";

            $param = [
                "platform_id"=> $platform_id,
                "id_user"=> $request->input('user_id') !== null ?  $request->input('user_id'): $userData->id
            ];

            $sqlRegistered = "$sql AND
                                awb_training_user.date_attended is null AND
                                awb_training_user.date_rsvp is not null AND
                                awb_training_schedule.schedule_end_time > :currentTime";

            $sqlNotRegistered = "$sql AND
                                    awb_training_user.date_rsvp is null AND
                                    (SELECT
                                    schedule_end_time
                                    FROM awb_training_schedule AWS
                                    WHERE AWS.awb_training_id = awb_training.id
                                    ORDER BY AWS.schedule_end_time desc
                                    LIMIT 1) > :currentTime";

            $sqlAttended = "$sql AND
                                awb_training_user.date_rsvp is not null AND
                                awb_training_user.date_attended is not null";

            $sqlNotAttended = "$sql AND
                                awb_training_user.date_attended is null AND
                                awb_training_schedule.schedule_end_time < :currentTime";

            $mergeParam = array_merge($param,["currentTime" => $currentDateTime]);


            $countAll = DB_global::cz_result_set($sql,$param);
            $countRegistered = DB_global::cz_result_set($sqlRegistered,$mergeParam);
            $countNoRegistered = DB_global::cz_result_set($sqlNotRegistered,$mergeParam);
            $countAttended = DB_global::cz_result_set($sqlAttended,$param);
            $countNoAttended = DB_global::cz_result_set($sqlNotAttended,$mergeParam);

            return response()->json([
                'data0' => $countAll,
                'data1' => $countNoRegistered,
                'data2' => $countRegistered,
                'data3' => $countNoAttended,
                'data4' => $countAttended,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function showDate(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        $schedule_id = $request->input('schedule_id');
        $type = $request->input('type');
        try {
            if($type == 'new'){
                $trainingSchedule = awb_training::where('id', '=', $training_id)->first();
                $scheduleList = DB::table('awb_training')->selectRaw("
                        awb_training.*, awb_training_schedule.capacity, awb_training_schedule.id as scheduleId,
                        (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                        DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                        DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                        DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                        DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                    ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                    ->where('awb_training.platform_id', '=', $platform_id)
                    ->where('awb_training.id', '=', $training_id)
                    ->whereNull('awb_training.user_deleted')
                    ->whereNull('awb_training_schedule.user_deleted')
                    ->where('awb_training_schedule.registration_end_date', '>=', DB_global::Global_CurrentDatetime())
                    ->get();
            } else {
                $trainingUser = awb_training_user::where('id', '=', $schedule_id)->first();
                $trainingSchedule = awb_training_schedule::where('id', '=', $trainingUser->awb_training_schedule_id)->first();
                $scheduleList = DB::table('awb_training')->selectRaw("
                        awb_training.*, awb_training_schedule.capacity, awb_training_schedule.id as scheduleId,
                        (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                        DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                        DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                        DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                        DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo")
                    ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                    ->where('awb_training.platform_id', '=', $platform_id)
                    ->where('awb_training.id', '=', $trainingSchedule->awb_training_id)
                    ->where('awb_training_schedule.id', '!=', $trainingSchedule->id)
                    ->whereNull('awb_training.user_deleted')
                    ->whereNull('awb_training_schedule.user_deleted')
                    ->where('awb_training_schedule.registration_end_date', '>=', DB_global::Global_CurrentDatetime())
                    ->get();
            }
            return response()->json([
                'data' => $scheduleList,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ImportDataEmployee(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        $schedule_id = $request->input('schedule_id');
        try {
            $schedule = awb_training_schedule::where('id','=', $schedule_id)->select('awb_training_id', 'capacity')->first();
            $awb_training_id = $schedule->awb_training_id;
            $capacity = $schedule->capacity;
            $training_id = awb_training::where('id', '=', $awb_training_id);
            if($request->hasFile('training_file'))
            {
                $file = $request->file('training_file');
                $fileName = $userData->account. '_' .$file->getClientOriginalName();
                $fileName = DB_global::cleanFileName($fileName);
                Storage::putFileAs('learn/training', $file, $fileName, 'public');
            }
            Excel::import(new TrainingEmployeeImport($userData->id, $platform_id, $capacity, $training_id, $schedule_id), $file);
            
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListDataScheduleUser(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $awb_training_schedule_id = $request->input('awb_training_schedule_id');
        try {
            $query = awb_training_user::select('awb_training_user.*'
                    ,'users.account', 'users.name')
                    ->leftJoin('users','users.id','=','awb_training_user.employee_id')
                ->when(isset($awb_training_schedule_id), function($query) use($awb_training_schedule_id){
                    $query->where(DB::RAW('md5(awb_training_user.awb_training_schedule_id)'), '=', $awb_training_schedule_id);
                });
            if($category != "COUNT"){
                $data = $query->limit($limit)
                    ->offset($offset)
                    ->orderBy('users.employee_id', 'asc')
                    ->orderBy('users.name', 'asc')
                    ->get();
            } else {
                $data = $query->count();
            }
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

}
