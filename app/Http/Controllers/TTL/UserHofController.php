<?php

namespace App\Http\Controllers\TTL;

use DB_global;
use App\Models\TTL\User;
use App\Models\TTL\dialogue_user_hof;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserHofController extends Controller
{
    protected $table_name = 'dialogue_user_hof';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $sql = "select * from $this->table_name where platform_id = :platform_id order by sort_index asc";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT")
        {
            $sql = $sql . " LIMIT :offset, :limit ";
            $param = array(
                'limit'=>$limit,
                'offset'=>$offset,
                'platform_id'=>$platform_id
                );
        }else{
            $param = array(
                'platform_id'=>$platform_id
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
        $directorate = $request->input('directorate');
        $platform_id = $request->input('platform_id');
        // $sql = "select * from $this->table_name where md5(id) = ? limit 1";
        try {
            // $data = DB_global::cz_result_array($sql,[$id]);
            $data = dialogue_user_hof::select('*')
                ->when(isset($id), function ($data) use ($id){
                    $data->where(DB::raw('md5(id)'), $id);
                })
                ->when(isset($directorate), function ($data) use ($directorate, $platform_id){
                    $data->where('directorate', $directorate)
                        ->where('platform_id', $platform_id);
                })
                ->orderBy('sort_index', 'asc')
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

    public function GetDistinctFunction(Request $request)
    {
        $platform_id = $request->input('md5ID');
        try {
            $sql = "SELECT DISTINCT directorate FROM dialogue_user_hof WHERE md5(platform_id) = ?";
            $data = DB_global::cz_result_set($sql, [$platform_id], false, '');
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

    public function GetAllFunction(Request $request)
    {
        $id = $request->input('md5ID');
        $sqlDtl2 = "SELECT directorate FROM dialogue_platform_dtl_2 WHERE md5(platform_id) = ?";
        $countDtl2 = DB_global::cz_result_set($sqlDtl2, [$id], false, 'COUNT');

        try {
            if($countDtl2 == 0){
                $function = new DB_global;
                $data = $function->function();
            } else {
                $data = DB_global::cz_result_set($sqlDtl2, [$id]);
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

    public function GetAllEmployee(Request $request)
    {
        ini_set('memory_limit','512M');
        $id = $request->input('md5ID');
        $directorate = $request->input('directorate');
        $sqlDtl1 = "SELECT country FROM dialogue_platform_dtl_1 WHERE md5(platform_id) = ?";
        $countDtl1 = DB_global::cz_result_set($sqlDtl1, [$id], false, 'COUNT');

        try {
            $countries = [];
            if($countDtl1 > 0){
                $countries = DB_global::cz_result_set($sqlDtl1, [$id]);
            }
            $data = User::select('id','account','name')
                ->where('status_active', 1)
                ->whereNotNull('directorate')
                ->when(isset($directorate), function ($data) use ($directorate){
                    $data->where('directorate', $directorate);
                })
                ->when($countDtl1 > 0, function ($data) use ($countries) {
                    $data->whereIn('country', $countries);
                })
                ->orderBy('id', 'asc')
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
            where  id = :id
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

    function group_by($data) {
        $result = array();
        $tmp = array();
        foreach($data as $arg)
        {
            $arg = (array) $arg;
            $tmp[$arg['directorate']][] = ['name'=>$arg['name'],'id'=>$arg['id']];
            // print_r($tmp);
        }
        foreach($tmp as $directorate => $employee)
        {
            $result[] = array(
                'directorate' => $directorate,
                'hof' => $employee
            );
        }
        return $result;
    }

    public function getListYawaHof(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $sql = "SELECT id, name, directorate FROM $this->table_name WHERE platform_id = :platform_id";

        try {
            $temp = DB_global::cz_result_set($sql,['platform_id'=> $platform_id], false);
            $data = $this->group_by($temp);
            // print_r($newArr);
            return response()->json([
                'data' => $data,
                'message' => 'OK'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
