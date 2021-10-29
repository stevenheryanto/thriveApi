<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_training;
use App\Models\AWB\awb_training_user;
use App\Models\AWB\awb_training_schedule;

class TrainingReportController extends Controller
{
    public function ListData(Request $request)
    {
        /* ListData for By Schedule Date use ListData function in TrainingScheduleController */
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $category = $request->input('category');
        try {
            $query = DB::table('awb_training')
                ->where('platform_id', '=', $platform_id)
                ->when(isset($filter_search), function($query) use($filter_search){
                    $query->where('name', 'like', '%'.$filter_search.'%');
                });
            if($category != "COUNT"){
                $data = $query->orderBy('date_created','desc')
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

    public function TrainingDetail(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        try {
            $dataTraining = awb_training::where('id', '=', $training_id)->first();
            $dataSchedule = DB::table('awb_training')->selectRaw("
                    awb_training_schedule.*,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo,
                    (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                    awb_training.name")
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training_schedule.user_deleted')
                ->where('awb_training.id', '=', $training_id)
                ->get();
            $listUser = [];
            foreach($dataSchedule as $schedule){
                $listUser = array_merge($listUser, [$this->ListUserByScheduleId($schedule->id)]);
            }
            $totalUser =  DB::table('awb_training_user')
                ->leftJoin('awb_training_schedule', 'awb_training_user.awb_training_schedule_id', '=', 'awb_training_schedule.id')
                ->where('awb_training_schedule.awb_training_id', '=', $training_id)
                ->count();
            $totalSchedule = DB::table('awb_training')
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training_schedule.user_deleted')
                ->where('awb_training.id', '=', $training_id)
                ->count();
            return response()->json([
                'data1' => $dataTraining,
                'data2' => $dataSchedule,
                'data3' => $listUser,
                'data4' => $totalUser,
                'data5' => $totalSchedule,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ScheduleDetail(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $schedule_id = $request->input('schedule_id');
        try {
            $awb_training_id = awb_training_schedule::where('id', '=', $schedule_id)->value('awb_training_id');
            $dataTraining = awb_training::where('id', '=', $awb_training_id)->first();
            $dataSchedule = DB::table('awb_training')->selectRaw("
                    awb_training_schedule.*,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo,
                    (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                    awb_training.name")
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training_schedule.user_deleted')
                ->where('awb_training_schedule.id', '=', $schedule_id)
                ->get();
            $listUser = [];
            foreach($dataSchedule as $schedule){
                $listUser = array_merge($listUser, [$this->ListUserByScheduleId($schedule->id)]);
            }
            $totalUser = awb_training_user::where('awb_training_schedule_id', '=', $schedule_id)->count();
            return response()->json([
                'data1' => $dataTraining,
                'data2' => $dataSchedule,
                'data3' => $listUser,
                'data4' => $totalUser,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ExportDataTraining(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $training_id = $request->input('training_id');
        try {
            $dataTraining = awb_training::where('id', '=', $training_id)->first();
            $filename = "Training - ". $dataTraining->name.".xls";
            $dataSchedule = DB::table('awb_training')->selectRaw("
                    awb_training_schedule.*,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo,
                    (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                    awb_training.name")
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training_schedule.user_deleted')
                ->where('awb_training.id', '=', $training_id)
                ->get();
            $totalSchedule = DB::table('awb_training')
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training_schedule.user_deleted')
                ->where('awb_training.id', '=', $training_id)
                ->count();
            $totalUser =  DB::table('awb_training_user')
                ->leftJoin('awb_training_schedule', 'awb_training_user.awb_training_schedule_id', '=', 'awb_training_schedule.id')
                ->where('awb_training_schedule.awb_training_id', '=', $training_id)
                ->count();
            $tableData = "<table border='1px' style='border-collapse:collapse' id='training'>
                <tr>
                    <td colspan='6'>
                        ".$dataTraining->name."
                    </td>
                </tr>
                <tr>
                    <td>Total Schedule</td>
                    <td colspan='5'>
                        ".$totalSchedule."
                    </td>
                </tr>
                <tr>
                    <td>Total Employee</td>
                    <td colspan='5'>
                        ".$totalUser."
                    </td>
                </tr>
                <tr>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th> 
                    <th>End Registration</th>
                    <th>Capacity</th>
                    <th>Total Employee</th>
                </tr>";
            foreach($dataSchedule as $schedule){
                $tableData .= "<tr>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_date_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_start_time_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_end_time_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->registration_end_date_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->capacity ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->total_user ."</td>
                    </tr>";
                $tableUser = $this->GenerateUserTable($schedule->id);
                $tableData .= $tableUser;
            }
            $tableData .= "</table>";

            return response()->json([
                'data1' => $tableData,
                'data2' => $filename,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ExportDataSchedule(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $schedule_id = $request->input('schedule_id');
        try {
            $dataTrainingSchedule = awb_training_schedule::where('id', '=', $schedule_id)->first();
            $dataTraining = awb_training::where('id', '=', $dataTrainingSchedule->awb_training_id)->first();
            $filename = "Training - ".$dataTraining->name." - ".$dataTrainingSchedule->schedule_date.".xls";
            $dataSchedule = DB::table('awb_training')->selectRaw("
                    awb_training_schedule.*,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo,
                    (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                    awb_training.name")
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training_schedule.user_deleted')
                ->where('awb_training_schedule.id', '=', $schedule_id)
                ->get();
            $tableData = "<table border='1px' style='border-collapse:collapse' id='schedule'>
                <tr>
                    <th colspan='6'>".$dataTraining->name."</th>
                </tr>
                <tr>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th> 
                    <th>End Registration</th>
                    <th>Capacity</th>
                    <th>Total Employee</th>
                </tr>";
            foreach($dataSchedule as $schedule){
                $tableData .= "<tr>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_date_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_start_time_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_end_time_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->registration_end_date_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->capacity ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->total_user ."</td>
                    </tr>";
                $tableUser = $this->GenerateUserTable($schedule->id);
                $tableData .= $tableUser;
            }
            $tableData .= "</table>";
            return response()->json([
                'data1' => $tableData,
                'data2' => $filename,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ExportDataScheduleRange(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        try {
            $filename = "Report Schedule - ".$filter_period_from." - ".$filter_period_to.".xls";
            $dataSchedule = DB::table('awb_training')->selectRaw("
                    awb_training_schedule.*,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo,
                    (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                    awb_training.name")
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->whereNull('awb_training_schedule.user_deleted')
                ->whereBetween('awb_training_schedule.schedule_date', [$filter_period_from, $filter_period_to])
                ->get();
            $tableData = "<table border='1px' style='border-collapse:collapse' id='schedule_range'>";
            foreach($dataSchedule as $schedule){
                $tableData .= "<tr>
                        <th colspan='6'>".$schedule->name."</th>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th> 
                        <th>End Registration</th>
                        <th>Capacity</th>
                        <th>Total Employee</th>
                    </tr>
                    <tr>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_date_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_start_time_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->schedule_end_time_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->registration_end_date_indo ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->capacity ."</td>
                        <td style='background-color:#ddd;color:#000;'>".$schedule->total_user ."</td>
                    </tr>";
                $tableUser = $this->GenerateUserTable($schedule->id);
                $tableData .= $tableUser;
            }
            $tableData .= "</table>";
            return response()->json([
                'data1' => $tableData,
                'data2' => $filename,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function ListUserByScheduleId($schedule_id)
    {
        try {
            $data = awb_training_user::select('awb_training_user.*','users.account','users.name')
                ->leftJoin('users', 'users.id', '=', 'awb_training_user.employee_id')
                ->where('awb_training_user.awb_training_schedule_id', '=', $schedule_id)
                ->orderBy('users.employee_id')
                ->orderBy('users.name')
                ->get();
            return $data;
        } catch (\Throwable $th) {
            return $th;
        }
    }

    function GenerateUserTable($schedule_id)
    {
        try {
            $tableUser = "<tr>
                    <th>Employee Id</th>
                    <th colspan='2'>Account Name</th>
                    <th>Name</th> 
                    <th>Registration Date</th>
                    <th>Attended Date</th>
                </tr>";
            $listUser = $this->ListUserByScheduleId($schedule_id);
            foreach($listUser as  $user){
                if($user->date_rsvp != ''){
                    $rsvp = $user->date_rsvp;
                } else {
                    $rsvp = "-";
                }
                if($user->date_attended != ''){
                    $attended = $user->date_attended;
                } else {
                    $attended = "-";
                }
                $tableUser .= "<tr>
                        <td>".$user->employee_id."</td>
                        <td colspan='2'>".$user->account."</td>
                        <td>".$user->name."</td>
                        <td>".$rsvp."</td>
                        <td>".$attended."</td>
                    </tr>";
            }
            return $tableUser;
        } catch (\Throwable $th) {
            return $th;
        }
    }
}