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

class TrainingScheduleController extends Controller
{
    protected $table_name = 'awb_training_schedule';

    public function ListData(Request $request)
    {
        /* ListDataByDate use startDate & endDate */
        $platform_id = $request->input('platform_id');
        $schedule_id = $request->input('schedule_id');
        $training_id = $request->input('training_id');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $category = $request->input('category');
        $active_only= $request->input('active_only'); //boolean. if false, get all data. if true, exclude deleted data;

        try {
            $query = DB::table('awb_training')->selectRaw("
                    awb_training_schedule.*,
                    DATE_FORMAT(awb_training_schedule.schedule_date, '%Y-%m-%d') as schedule_date_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_start_time, '%H:%i') as schedule_start_time_indo,
                    DATE_FORMAT(awb_training_schedule.schedule_end_time, '%H:%i') as schedule_end_time_indo,
                    DATE_FORMAT(awb_training_schedule.registration_end_date, '%Y-%m-%d') as registration_end_date_indo,
                    (SELECT count(*) from awb_training_user where awb_training_schedule_id = awb_training_schedule.id ) as total_user,
                    awb_training.name")
                ->join('awb_training_schedule', 'awb_training.id', '=', 'awb_training_schedule.awb_training_id')
                ->where('awb_training.platform_id', '=', $platform_id)
                ->when(isset($startDate, $endDate), function($query) use($startDate, $endDate){
                    $query->whereBetween('awb_training_schedule.schedule_date', [$startDate, $endDate]);
                })
                ->when(isset($training_id), function($query) use($training_id){
                    $query->where('awb_training.id', '=', $training_id);
                })
                ->when(isset($schedule_id), function($query) use($schedule_id){
                    $query->where('awb_training_schedule.id', '!=', $schedule_id);
                })
                ->when(isset($active_only), function($query) use($active_only){
                    if($active_only){
                        $query->whereNull('awb_training_schedule.date_deleted');
                    }
                });
            if($category != "COUNT"){
                $data = $query->orderBy('awb_training_schedule.schedule_date')
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
    

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();


        $_arrayData = $request->except('id','schedule_end_time','schedule_start_time');

        $schedule_start_time = $request->input('schedule_date')." ".$request->input('schedule_start_time');
        $schedule_end_time = $request->input('schedule_date')." ".$request->input('schedule_end_time');

        $_arrayData = array_merge($_arrayData,
            array(
                'schedule_end_time'=> $schedule_end_time ,
                'schedule_start_time'=> $schedule_start_time ,
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
        $_arrayData = $request->input();
        $id = $request->input('id');        


        $_arrayData = $request->except('id','schedule_end_time','schedule_start_time');

        $schedule_start_time = $request->input('schedule_date')." ".$request->input('schedule_start_time');
        $schedule_end_time = $request->input('schedule_date')." ".$request->input('schedule_end_time');

        $_arrayData = array_merge($_arrayData,
            array(
                'schedule_end_time'=> $schedule_end_time ,
                'schedule_start_time'=> $schedule_start_time ,
                'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name,'id', $id, $_arrayData);
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
            $query = DB::table($this->table_name.' as a')->selectRaw("
                a.id,
                a.awb_training_id,    
                a.hyperlink_url, 
                a.capacity, 
                DATE_FORMAT(a.schedule_date, '%Y-%m-%d') as schedule_date,
                DATE_FORMAT(a.schedule_start_time, '%H:%i') as schedule_start_time,
                DATE_FORMAT(a.schedule_end_time, '%H:%i') as schedule_end_time,
                DATE_FORMAT(a.registration_end_date, '%Y-%m-%d') as registration_end_date,
                (select count(*) from awb_training_user where awb_training_schedule_id = a.id ) as total_user,
                b.name as name_created,
                c.name as name_modified,
                d.name as training_name")
                ->leftJoin('users as b', 'a.user_created', '=', 'b.id')
                ->leftJoin('users as c', 'a.user_modified', '=', 'c.id')
                ->leftJoin('awb_training as d', 'a.awb_training_id', '=', 'd.id')
                ->where(DB::RAW('md5(a.id)'), '=', $id);

            $data = $query->first();

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
        $user_deleted = $request->input('user_deleted');
        try {
            // $data = DB_global::cz_delete($this->table_name,'id',$id);
            $_arrayData = array(
				'user_deleted'=>$user_deleted,
				'date_deleted'=>DB_global::Global_CurrentDatetime()
			);	
            $data = DB_global::cz_update($this->table_name,'id', $id, $_arrayData);
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

}
