<?php

namespace App\Http\Controllers\TTL;

use DB_global;
use App\Exports\DialogueEventExport;
use App\Exports\DialogueFeedbackExport;
use App\Jobs\SendEmailDialogue;
use App\Models\TTL\User;
use App\Models\TTL\dialogue_event;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class EventController extends Controller
{
    protected $table_name = 'dialogue_event';

    public function ListData(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $rating_flag = $request->input('rating_flag');

        try {
            $query = dialogue_event::select(
                'dialogue_event.id',
                'c.name as employee_name',
                'c.id as user_id',
                'd.name as initiator_name',
                'd.id as initiator_id',
                'b.title',
                'b.place',
                'b.schedule_date',
                'b.schedule_time',
                'b.total_participant',
                'dialogue_event.date_created',
                'c.directorate as employee_function',
                'd.directorate as initiator_function',
                'dialogue_event.flag_check_mark',
                'dialogue_event.email_confirmation_flag',
                'dialogue_event.email_hold_flag',
                'dialogue_event.email_congratulatory_flag',
                'dialogue_event.email_feedback_flag',
                'dialogue_event.rating_reason',
                'dialogue_event.rating_score',
                'dialogue_event.rating_date'
            )
            ->leftJoin('dialogue_event_schedule as b', 'dialogue_event.event_id', '=', 'b.id')
            ->leftJoin('users as c', 'dialogue_event.user_id', '=', 'c.id')
            ->leftJoin('users as d', 'dialogue_event.user_created', '=', 'd.id')
            ->where('dialogue_event.is_deleted', '=', 0)
            ->where('dialogue_event.platform_id', '=', $platform_id)
            ->where('b.is_deleted', '=', 0)
            ->when(isset($rating_flag),
                function ($query) use($rating_flag) {
                $query->where('dialogue_event.rating_flag', '=', $rating_flag);
            })
            ->when(isset($filter_search),
                function ($query) use($filter_search) {
                $query->where('b.title', 'like', '%'.$filter_search.'%')
                ->orWhere('c.name', 'like', '%'.$filter_search.'%')
                ->orWhere('d.name', 'like', '%'.$filter_search.'%')
                ->orWhere('b.place', 'like', '%'.$filter_search.'%');
            })
            ->when(isset($filter_period_from) && isset($filter_period_to),
                function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(dialogue_event.date_created, date)'), [$filter_period_from, $filter_period_to]);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('dialogue_event.id', 'desc')
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
        $_arrayData = array_merge($_arrayData,
        array(
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
        /* UpdateEventRating use this */
        $id = $request->input('id');
        $all = $request->except('user_account','id');
        $all = array_merge($all, [
            'date_modified' => DB_global::Global_CurrentDatetime(),
            'rating_date' => DB_global::Global_CurrentDatetime()
        ]);
        try {
            $data = DB_global::cz_update($this->table_name, DB::raw('md5(id)'), $id, $all);
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

    public function CheckUserRegistered(Request $request)
	{
            /* CheckEventDialogueRating use this */
        $platform_id = $request->input('platform_id');
        $id = $request->input('id');
        $event_id = $request->input('event_id');
        $user_id = $request->input('user_id');

        $sql = dialogue_event::select('*')
            ->where('platform_id', '=', $platform_id)
            ->where('user_id', '=', $user_id)
            ->when(isset($id), function ($sql) use($id) {
                $sql->where(DB::raw('md5(id)'), '=', $id)
                ->where('rating_flag', '=', 0);
            })
            ->when(isset($event_id), function ($sql) use($event_id) {
                $sql->where('event_id', '=', $event_id);
            })
            ->exists();
        try {
            /* return true if row exists, false if not exists */
            return response()->json([
                'data' => $sql,
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
                'data' => $data,
                'message' => 'validate success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'validate failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function UpdateFlagCheckMark(Request $request)
    {
        $id = $request->input('id');
        $flag_check_mark = $request->input('flag_check_mark');
        $param = array(
            'id' => $id,
            'flag_check_mark' => $flag_check_mark
        );
        $sql = "UPDATE dialogue_event
            SET flag_check_mark = :flag_check_mark,
            email_congratulatory_flag = IFNULL(email_congratulatory_flag, 0),
            email_feedback_flag = IFNULL(email_feedback_flag, 0)
            WHERE md5(id) = :id ";
        try {
            $data = DB_global::cz_execute_query($sql, $param);
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

    function UpdateFlagEmailCongratulatory($id, $user_id)
    {
        $email_congratulatory_sent_by = $user_id;
        $param = array(
            'id' => $id,
            'email_congratulatory_sent_by' => $email_congratulatory_sent_by
        );
        $sql = "UPDATE dialogue_event
            SET email_congratulatory_flag = 1,
            email_congratulatory_sent_date = now(),
            email_congratulatory_sent_by = :email_congratulatory_sent_by
            WHERE md5(id) = :id ";
        try {
            $data = DB_global::cz_execute_query($sql, $param);
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

    function UpdateFlagEmailFeedback($id, $user_id)
    {
        $email_feedback_sent_by = $user_id;
        $param = array(
            'id' => $id,
            'email_feedback_sent_by' => $email_feedback_sent_by
        );
        $sql = "UPDATE dialogue_event
            SET email_feedback_flag = 1,
            email_feedback_sent_date = now(),
            email_feedback_sent_by = :email_feedback_sent_by
            WHERE md5(id) = :id ";
        try {
            $data = DB_global::cz_execute_query($sql, $param);
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

    function UpdateFlagEmailHold($id, $user_id)
    {
        $email_hold_sent_by = $user_id;
        $param = array(
            'id' => $id,
            'email_hold_sent_by' => $email_hold_sent_by
        );
        $sql = "UPDATE dialogue_event
            SET email_hold_flag = 1,
            email_hold_sent_date = now(),
            email_hold_sent_by = :email_hold_sent_by
            WHERE md5(id) = :id ";
        try {
            $data = DB_global::cz_execute_query($sql, $param);
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

    function UpdateFlagEmailConfirmation($id, $user_id)
    {
        $email_confirmation_sent_by = $user_id;
        $param = array(
            'id' => $id,
            'email_confirmation_sent_by' => $email_confirmation_sent_by
        );
        $sql = "UPDATE dialogue_event
            SET email_confirmation_flag = 1,
            email_confirmation_sent_date = now(),
            email_confirmation_sent_by = :email_confirmation_sent_by
            WHERE md5(id) = :id ";
        try {
            $data = DB_global::cz_execute_query($sql, $param);
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

    public function FormExport(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $rating_flag = $request->input('rating_flag');
        $dateStamp =  date('Ymdhms');

        try {
            $folder_name = 'listen/temp/';
            if($rating_flag == 1){
                $excelName = 'report_dialogue_feedback_'.$dateStamp. '.xlsx';
                Excel::store(new DialogueFeedbackExport($platform_id, $filter_search, $filter_period_from, $filter_period_to), $folder_name.$excelName);
            }else{
                $excelName = 'report_dialogue_event_'.$dateStamp. '.xlsx';
                Excel::store(new DialogueEventExport($platform_id, $filter_search, $filter_period_from, $filter_period_to),  $folder_name.$excelName);
            }
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            return Storage::get($path, 200, $headers);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function GetEvent(String $id)
    {
        $sql = "SELECT a.*, b.name as employee_name, b.email as employee_email
            FROM $this->table_name a
            LEFT JOIN users b ON a.user_id = b.id
            WHERE md5(a.id) = ? LIMIT 1";
        return DB_global::cz_result_array($sql, [$id]);
    }

    public function SendEmail(Request $request)
    {
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $theme_id = $request->input('theme_id');
        $case = $request->input('case');
        switch($case)
        {
            case 1:
                $this->UpdateFlagEmailConfirmation($id, $user_id);
                $message = "Confirmation email sent.";
                break;
            case 2:
                $this->UpdateFlagEmailHold($id, $user_id);
                $message = "Hold email sent.";
                break;
            case 3:
                $this->UpdateFlagEmailCongratulatory($id, $user_id);
                $message = "Congratulatory email sent.";
                break;
            case 4:
                $this->UpdateFlagEmailFeedback($id, $user_id);
                $message = "Feedback email sent.";
                break;
        }
        $sql_theme = "SELECT img_home_menu_gallery, img_email_logo FROM dialogue_theme WHERE platform_id = ? AND id = ?";
        $resultTheme = DB_global::cz_result_array($sql_theme, [$platform_id, $theme_id]);
        $resultEvent = $this->GetEvent($id);
        $sql_schedule = "SELECT * FROM dialogue_event_schedule WHERE id = ? LIMIT 1";
        $resultSchedule = DB_global::cz_result_array($sql_schedule, [$resultEvent['event_id']]);

        $img_home_menu_gallery = $resultTheme['img_home_menu_gallery'];
        $img_email_logo = $resultTheme['img_email_logo'];
        // if(isset($resultTheme['img_home_menu_gallery'])){
        //     $img_home_menu_gallery = $resultTheme['img_home_menu_gallery'];
        // } else {
        //     $img_home_menu_gallery = 'feature-gallery.jpg';    
        // }
        // if(isset($resultTheme['img_email_logo'])){
        //     $img_email_logo = $resultTheme['img_email_logo'];
        // } else {
        //     $img_email_logo = 'logo.png';    
        // }
        Log::info($img_home_menu_gallery);
        Log::info($img_email_logo);

        $details = [
            'md5Id' => $id,
            'case' => $case,
            'img_home_menu_gallery' => $img_home_menu_gallery,
            'img_email_logo' => $img_email_logo,
            'title' => $resultSchedule['title'],
            'time' => $resultSchedule['schedule_time'],
            'place' => $resultSchedule['place'],
            'scheduleDate' => $resultSchedule['schedule_date'],
            'toEmail' => $resultEvent['employee_email'],
            'toName' => $resultEvent['employee_name'],
        ];
        SendEmailDialogue::dispatch($details);

        try {
            return response()->json([
                'data' => '',
                'message' => $message
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
