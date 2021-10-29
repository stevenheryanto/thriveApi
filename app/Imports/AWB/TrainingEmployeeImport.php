<?php

namespace App\Imports\AWB;

use DB_global;
use App\Models\AWB\awb_training;
use App\Models\AWB\awb_training_user;
use App\Models\AWB\awb_training_schedule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;

class TrainingEmployeeImport implements OnEachRow
{
    protected $newIdTraining;
    protected $newIdSchedule;

    public function __construct($user_id, $platform_id, $capacity, $training_id, $schedule_id)
    {
        $this->user_id = $user_id;
        $this->platform_id = $platform_id;
        $this->capacity = $capacity;
        $this->training_id = $training_id;
        $this->schedule_id = $schedule_id;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();
        If(DB::table('awb_training_user')
            ->join('awb_training_schedule','awb_training_schedule.id', '=', 'awb_training_user.awb_training_schedule_id')
            ->join('awb_training','awb_training.id', '=', 'awb_training_schedule.awb_training_id')
            ->where('awb_training.platform_id', '=', $this->platform_id)
            ->where('awb_training_user.employee_id', '=', $row[0])
            ->where('awb_training_schedule.awb_training_id', '= ', $this->training_id)
            ->doesntExist()){
                $totalUser = awb_training_user::where('awb_training_schedule_id', '=', $this->schedule_id)->count();
                if($totalUser){
                    $totalUserNow = $totalUser;
                } else {
                    $totalUserNow = 0;
                }
                if($totalUserNow < $this->capacity){
                    $arrayData = array(
                        'awb_training_schedule_id' => $this->schedule_id,								
                        'employee_id' => $row[0],
                        'user_created' => $this->user_id,
                        'date_created' => DB_global::Global_CurrentDatetime()
                    );	
                    DB_global::cz_insert('awb_training_user', $arrayData);
                }
            }
    }
}