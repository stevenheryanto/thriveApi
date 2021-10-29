<?php

namespace App\Imports\AWB;

use DB_global;
use App\Models\AWB\awb_training;
use App\Models\AWB\awb_training_user;
use App\Models\AWB\awb_training_schedule;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;

class TrainingImport implements OnEachRow
{
    protected $newIdTraining;
    protected $newIdSchedule;

    public function __construct($user_id, $platform_id, $filename)
    {
        $this->user_id = $user_id;
        $this->platform_id = $platform_id;
        $this->filename = $filename;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();
        $nameTraining = $row[0];
        switch($row[0]){
            case 'training':
                $arrayData = [
                    'name' => $row[1],								
                    'status_active'	=>  1,
                    'filename' => $this->filename,
                    'user_created' => $this->user_id,
                    'date_created' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $this->platform_id
                ];	
                $this->newIdTraining = DB_global::cz_insert('awb_training', $arrayData, TRUE);        
                break;
            case 'schedule':
                if($this->newIdTraining  > 0){
                    $arrayData = [
                        'awb_training_id' => $this->newIdTraining,								
                        'schedule_date' => $row[1],
                        'schedule_start_time' => $row[1]." ".$row[2],
                        'schedule_end_time' => $row[1]." ".$row[3],
                        'registration_end_date' => $row[5]." 23:59:00",
                        'hyperlink_url' => $row[6],
                        'capacity' => $row[4],
                        'total_user' => 0,
                        'user_created' => $this->user_id,
                        'date_created' => DB_global::Global_CurrentDatetime()
                    ];	
                    $this->newIdSchedule  = DB_global::cz_insert('awb_training_schedule', $arrayData, TRUE);   
                }   
                break;
            case 'employee_id':
                if($this->newIdSchedule > 0){
                    $arrayData = [
                        'awb_training_schedule_id' => $this->newIdSchedule,							
                        'employee_id' =>  $row[1],                   
                        'user_created' => $this->user_id,
                        'date_created' => DB_global::Global_CurrentDatetime()
                    ];
                    DB_global::cz_insert('awb_training_user', $arrayData);
                    $arrayLog = [
                        'awb_training_id' => $this->newIdTraining,							
                        'awb_training_schedule_id' =>  $this->newIdSchedule,                        
                        'employee_id' => $row[1],
                        'date_created' => DB_global::Global_CurrentDatetime(),
                        'platform_id' => $this->platform_id
                    ];
                    DB_global::cz_insert('awb_training_log_schedule', $arrayLog);
				}
                break;
        }
    }
}