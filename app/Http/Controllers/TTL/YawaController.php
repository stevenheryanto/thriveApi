<?php

namespace App\Http\Controllers\TTL;

use DB_global;
use App\Exports\YawaExport;
use App\Models\TTL\dialogue_yawa;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class YawaController extends Controller
{
    protected $table_name = 'dialogue_yawa';

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
            $query = dialogue_yawa::select(
                'dialogue_yawa.id',
                DB::raw("ifnull(b.name, concat(dialogue_yawa.host_hof_other,' (other)')) as hof_name"),
                'b.directorate as hof_function',
                'c.name as initiator_name',
                'c.id as initiator_id',
                'c.directorate as initiator_func',
                'dialogue_yawa.message',
                'dialogue_yawa.date_created',
                'dialogue_yawa.flag_anonymous',
                'dialogue_yawa.flag_check_mark',
                'dialogue_yawa.notes'
            )
            ->leftJoin('dialogue_user_hof as b', 'dialogue_yawa.host_hof', '=', 'b.id')
            ->leftJoin('users as c', 'dialogue_yawa.user_created', '=', 'c.id')
            ->where('dialogue_yawa.is_deleted', '=', 0)
            ->where('dialogue_yawa.platform_id', '=', $platform_id)
            ->when(isset($filter_search),
                function ($query) use($filter_search) {
                $query->where('b.name', 'like', '%'.$filter_search.'%')
                ->orWhere('b.directorate', 'like', '%'.$filter_search.'%')
                ->orWhere('c.name', 'like', '%'.$filter_search.'%')
                ->orWhere('dialogue_yawa.message', 'like', '%'.$filter_search.'%')
                ->orWhere('dialogue_yawa.notes', 'like', '%'.$filter_search.'%')
                ;
            })
            ->when(isset($filter_period_from) && isset($filter_period_to),
                function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(dialogue_yawa.date_created, date)'), [$filter_period_from, $filter_period_to]);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('dialogue_yawa.id', 'desc')
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
        $id = $request->input('id');
        $all = $request->except('user_account','id');
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name, 'md5(id)', $id, $all);
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

    public function FormExport(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $dateStamp =  date('Ymdhms');

        try {
            $folder_name = 'listen/temp/';
            $excelName = 'report_yawa_'.$dateStamp. '.xlsx';

            Excel::store(new YawaExport( $platform_id, $filter_search, $filter_period_from, $filter_period_to), $folder_name.$excelName);
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
