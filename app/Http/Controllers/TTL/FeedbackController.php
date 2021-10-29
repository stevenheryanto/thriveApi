<?php

namespace App\Http\Controllers\TTL;

use DB_global;
use App\Exports\DialogueAppsFeedbackExport;
use App\Models\TTL\dialogue_feedback_list;
use App\Models\TTL\dialogue_feedback_user;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class FeedbackController extends Controller
{
    protected $table_name = 'dialogue_feedback_list';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $flag_is_like = $request->input('flag_is_like');
        $status_active = $request->input('status_active');

        try {
            $query = dialogue_feedback_list::select('*')
                ->where('platform_id', '=', $platform_id)
                ->when(isset($flag_is_like),
                    function ($query) use($flag_is_like) {
                    $query->where('flag_is_like', '=', $flag_is_like);
                })
                ->when(isset($status_active),
                    function ($query) use($status_active) {
                    $query->where('status_active', '=', $status_active);
                });
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('flag_is_like', 'asc')
                    ->orderBy('sort_index', 'asc')
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

    public function ListDataReport(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $flag_feedback = $request->input('flag_feedback');

        try {
            $query = dialogue_feedback_user::select(
                'b.name as employee_name',
                'dialogue_feedback_user.user_id',
                'b.directorate as employee_function',
                'dialogue_feedback_user.reason',
                'dialogue_feedback_user.flag_feedback',
                'dialogue_feedback_user.date_feedback',
                'dialogue_feedback_user.id')
            ->leftJoin('users as b', 'dialogue_feedback_user.user_id', '=', 'b.id')
            ->where('b.status_active', '=', 1)
            ->when(isset($flag_feedback),
                function ($query) use($flag_feedback) {
                $query->where('dialogue_feedback_user.flag_feedback', '=', $flag_feedback);
            })
            ->when(isset($filter_search),
                function ($query) use($filter_search) {
                $query->where('b.name', 'like', '%'.$filter_search.'%')
                ->orWhere('dialogue_feedback_user.user_id', 'like', '%'.$filter_search.'%')
                ->orWhere('c.directorate', 'like', '%'.$filter_search.'%')
                ->orWhere('dialogue_feedback_user.reason', 'like', '%'.$filter_search.'%');
            })
            ->when(isset($filter_period_from) && isset($filter_period_to),
                function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(dialogue_feedback_user.date_feedback, date)'), [$filter_period_from, $filter_period_to]);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('dialogue_feedback_user.id', 'desc')
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

    public function InsertFeedbackUser(Request $request)
    {
        $_arrayData = $request->input();
        $_arrayData = array_merge($_arrayData,
        array(
            'date_feedback'=> DB_global::Global_CurrentDatetime(),
        ));
        try {
            $data = DB_global::cz_insert('dialogue_feedback_user', $_arrayData, false);
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
        $id = $request->input('id');
        $all = $request->except('user_account','id');
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name, 'id', $id, $all);
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

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::bool_ValidateDataOnTableById_md5($this->table_name, $id);
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

    public function SelectData(Request $request)
    {
        $id = $request->input('md5ID');
        $sql = "select * from $this->table_name where md5(id) = ? limit 1";
        try {
            $data = DB_global::cz_result_array($sql,[$id]);

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

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $hdr = DB_global::cz_delete($this->table_name, 'id', $id);

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

    public function MoveUp(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');

        $sql = "update $this->table_name set sort_index = sort_index + 1
            where (sort_index >= (:sort_index - 1) and sort_index <> :sort_index2)
            and platform_id = :platform_id";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index,
            'platform_id' => $platform_id
        ];

        $sql2 = "update $this->table_name set sort_index = sort_index - 1
            where id = :id
            and platform_id = :platform_id";
        $param2 = ['id'=>$id,
            'platform_id'=>$platform_id
        ];
        try {
            $data = DB_global::cz_execute_query($sql, $param);
            $data2 = DB_global::cz_execute_query($sql2, $param2);
            $this->ReSortingIndex($platform_id);

            return response()->json([
                'data' => $data,
                'data2' => $data2,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function MoveDown(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');

        $sql = "update $this->table_name set sort_index = sort_index - 1
            where (sort_index <= (:sort_index + 1) and sort_index <> :sort_index2)
            and platform_id = :platform_id";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index,
            'platform_id' => $platform_id
        ];
        $sql2 = "update $this->table_name set sort_index = sort_index + 1
            where id = :id
            and platform_id = :platform_id ";
        $param2 = ['id'=>$id,
            'platform_id'=>$platform_id
        ];
        try {
            $data = DB_global::cz_execute_query($sql, $param);
            $data2 = DB_global::cz_execute_query($sql2, $param2);
            $this->ReSortingIndex($platform_id);

            return response()->json([
                'data' => $data,
                'data2' => $data2,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    function ReSortingIndex($platform_id)
	{
        DB::statement(DB::raw('set @rownum = 0'));
        DB::table($this->table_name)
            ->where('platform_id', $platform_id)
            ->orderBy('sort_index', 'asc')
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum + 1'),
            ]);
	}

    public function GenerateActivityFeedback(Request $request)
    {
        $user_id = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $param = array(
            'user_id' => $user_id,
            'platform_id' => $platform_id
        );
        if (DB::table('dialogue_activity')->where('user_id', $user_id)->where('platform_id', $platform_id)->doesntExist()) {
            try {
                $data = DB_global::cz_insert('dialogue_activity', $param, false);
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
    }

    public function CheckFeedbackSession(Request $request)
    {
        $user_id = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $param = array(
            'user_id' => $user_id,
            'platform_id' => $platform_id
        );
        $sql = "SELECT * FROM dialogue_activity
            WHERE user_id = :user_id
            AND platform_id = :platform_id
            AND flag_feedback = 0";
        try {
            $data = DB_global::bool_CheckRowExist($sql, $param);
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

    public function UpdateActivity(Request $request)
    {
        $user_id = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $all = $request->except('user_account','id');
        $all = array_merge($all, [
            'flag_feedback' => 1,
            'date_feedback' => DB_global::Global_CurrentDatetime()
        ]);
        try {
            $data = DB_global::cz_update('dialogue_activity', 'user_id', $user_id, $all);
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

    public function FlagReset(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $sql = "UPDATE dialogue_activity
            SET flag_feedback = 0, date_feedback = null
            WHERE platform_id = :platform_id";
        try {
            $data = DB_global::cz_execute_query($sql, [$platform_id]);
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
        $flag_feedback = $request->input('flag_feedback');
        $dateStamp =  date('Ymdhms');

        try {
            $folder_name = 'listen/temp/';
            $excelName = 'report_apps_feedback_'.$dateStamp. '.xlsx';
            Excel::store(new DialogueAppsFeedbackExport($platform_id, $filter_search, $filter_period_from, $filter_period_to, $flag_feedback), $folder_name.$excelName);
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
}
