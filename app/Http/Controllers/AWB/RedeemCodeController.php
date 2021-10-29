<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Exports\AWB\RedeemCodeExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class RedeemCodeController extends Controller
{
    protected $table_name = 'awb_trn_redeem_code_list';

    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');

        try {
            $query = DB::table($this->table_name.' as a')
                ->select('a.*', 'b.total_claim')
                ->leftJoin(DB::raw("(
                    select c.redeem_code_list_id,count(c.id) as total_claim from awb_trn_redeem_code c where 
                    c.date_redeem is not null and c.platform_id = '".$platform_id."' group by c.redeem_code_list_id)
                b"),
                'a.id', '=', 'b.redeem_code_list_id')
                ->where('a.platform_id', '=', $platform_id);

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('a.id','desc')
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
            $query = DB::table($this->table_name.' as a')
                ->select('a.*', 'b.total_claim')
                ->leftJoin(DB::raw('(
                    select c.redeem_code_list_id,count(c.id) as total_claim from awb_trn_redeem_code c where 
                    c.date_redeem is not null group by c.redeem_code_list_id)
                b'),
                'a.id', '=', 'b.redeem_code_list_id')
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

    public function ListDataClaim(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');
        $offset = $request->input('offset');
        $limit = $request->input('limit');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        try {
            $query = DB::table('awb_trn_redeem_code as a')
                ->selectRaw('a.*, ifnull(b.name,b.full_name) as name, b.account as user_account, b.id as user_id')
                ->leftJoin('users as b', 'a.user_id', '=', 'b.id')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($filter_period_from, $filter_period_to),
                function ($query) use ($filter_period_from, $filter_period_to) {
                    $query->whereBetween(DB::raw('convert(a.date_redeem,date)'), [$filter_period_from, $filter_period_to]);
            });
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('a.id', 'desc')
                    ->get();
            } else {
                $data = $query->count();
            }
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ExportDataClaim(Request $request)
	{	
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $dateStamp =  date('Ymdhms');
        try {
            $folder_name = 'learn/temp/';
            $excelName = 'report_redeem_code_'.$dateStamp. '.xlsx';
            Excel::store(new RedeemCodeExport($platform_id, $filter_period_from, $filter_period_to), $folder_name.$excelName);
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
