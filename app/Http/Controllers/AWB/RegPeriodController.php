<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_mst_reg_period;

class RegPeriodController extends Controller
{
    protected $table_name = 'awb_mst_reg_period';

    
    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');
        $sort_by    = $request->input('sortBy');
        
        try {

            $query = DB::table($this->table_name .' as a')->select('a.*'
                )->where('a.platform_id', '=', $platform_id)
                ;

            switch($sort_by)
            {
                case 'last_modified':
                    $query=$query-> orderBy('a.date_modified', 'desc');
                    break;
                case 'earliest_from':
                    $query=$query-> orderBy('a.reg_from', 'asc');
                    break;
                case 'latest_from':
                    $query=$query-> orderBy('a.reg_from', 'desc');
                    break;
                case 'earliest_to':
                    $query=$query-> orderBy('a.reg_to', 'asc');
                    break;
                case 'latest_to':
                    $query=$query-> orderBy('a.reg_to', 'desc');
                    break;
                default:
                    $query=$query-> orderBy('a.date_modified', 'desc');
                    break;
            }

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
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
        $all = $request->except('id','user_account');

        //var_dump($all);exit();
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name,'id', $id, $all);
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
            $query = DB::table($this->table_name .' as a')->select('a.*'
                )->where(DB::RAW('md5(a.id)'), '=', $id)
                ;
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
        try {
            $data = DB_global::cz_delete($this->table_name,'id',$id);
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
}
