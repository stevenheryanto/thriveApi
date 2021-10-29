<?php

namespace App\Http\Controllers\TTL;

use DB;
use DB_global;
use App\Exports\InitiateDialogueExport;
use App\Models\TTL\User;
use App\Models\TTL\dialogue_make_your_own;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class InitiateController extends Controller
{
    protected $table_name = 'dialogue_make_your_own';

    public function ListData(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');

        try {
            $query = dialogue_make_your_own::select(
                'dialogue_make_your_own.id',
                'b.name',
                'b.directorate',
                'c.name as employee_name', 
                'c.id as user_id', 
                'c.directorate as employee_function',
                'd.name as initiator_name', 
                'd.id as initiator_id', 
                'd.directorate as initiator_function',
                'dialogue_make_your_own.topic',
                'dialogue_make_your_own.date_created',
                'dialogue_make_your_own.flag_check_mark'
            )
            ->leftJoin('dialogue_user_hof as b', 'dialogue_make_your_own.host_hof', '=', 'b.id')
            ->leftJoin('users as c', 'dialogue_make_your_own.user_id', '=', 'c.id')
            ->leftJoin('users as d', 'dialogue_make_your_own.user_created', '=', 'd.id')
            ->where('dialogue_make_your_own.is_deleted', '=', 0)
            ->where('dialogue_make_your_own.platform_id', '=', $platform_id)
            ->when(isset($filter_search), 
                function ($query) use($filter_search) {
                $query->where('b.name','like', '%'.$filter_search.'%')
                ->orWhere('c.name','like', '%'.$filter_search.'%')
                ->orWhere('d.name','like', '%'.$filter_search.'%')
                ->orWhere('b.directorate','like', '%'.$filter_search.'%');
            })
            ->when(isset($filter_period_from) && isset($filter_period_to),
                function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(dialogue_make_your_own.date_created, date)'), [$filter_period_from, $filter_period_to]);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('dialogue_make_your_own.id', 'desc')
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

    public function UpdateFlagCheckMark(Request $request)
    {
        $id = $request->input('id');
        $flag_check_mark = $request->input('flag_check_mark');
        $param = array(
            'id' => $id,
            'flag_check_mark' => $flag_check_mark
        );
        $sql = "UPDATE $this->table_name SET flag_check_mark = :flag_check_mark WHERE md5(id) = :id";
        try {
            $data = DB_global::cz_execute_query($sql, $param);
            return response()->json([
                'data'      => $data,
                'message'   => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function FormExport(Request $request)
    {
        ini_set('max_execution_time', 180);
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $dateStamp =  date('Ymdhms');

        try {
            $folder_name = 'listen/temp/';
            $excelName = 'report_initiate_'.$dateStamp. '.xlsx';

            Excel::store(new InitiateDialogueExport( $platform_id, $filter_search, $filter_period_from, $filter_period_to), $folder_name.$excelName);
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

	public function CheckUserRegistered(Request $request)
	{
        $host_hof = $request->input('host_hof');
        $user_id = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $param = array(
            'host_hof' => $host_hof,
            'user_id' => $user_id,
            'platform_id' => $platform_id
        );
		$sql = "SELECT * FROM $this->table_name 
            WHERE host_hof = :host_hof 
            AND user_id = :user_id 
            AND platform_id = :platform_id";
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

}