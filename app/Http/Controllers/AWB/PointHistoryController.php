<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use App\Imports\AWB\PointHistoryImport;
use App\Jobs\SendEmailAwbPointHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_config;
use App\Models\AWB\awb_trn_point_history;
use App\Models\AWB\awb_tmp_point_history;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;

class PointHistoryController extends Controller
{
    protected $table_name = 'awb_trn_point_history';
    
    public function ListData(Request $request)
	{	
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');
        $limit = $request->input('limit');
        $offset = $request->input('offset');

        try {
            $query = DB::table('awb_trn_point_history as a')
                ->join('users as b', 'a.user_id', '=', 'b.id')
                ->selectRaw('a.id, a.user_id, a.point, a.source, ifnull(b.full_name,b.name) as name')
                ->whereRaw('date(a.date_modified) >= date(date_sub(CURRENT_DATE, interval 2 day))')
                ->where('a.platform_id', '=', $platform_id)
                ->orderBy('a.date_modified', 'desc');
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

    public function ImportData(Request $request)
    {
        $token = $request->bearerToken();
        $userData = JWTAuth::toUser($token);
        $platform_id = $request->input('platform_id');
        $configSendEmail = awb_mst_config::where('_code', '=', 'email_notification')->where('platform_id', '=', $platform_id)->value('value');

        try {
            awb_tmp_point_history::where('platform_id', '=', $platform_id)->delete();
            if($request->hasFile('pointHistory_file'))
            {
                $file = $request->file('pointHistory_file');
                $fileName = $userData->account. '_' .$file->getClientOriginalName();
                $fileName = DB_global::cleanFileName($fileName);
                Storage::putFileAs('learn/point_history', $file, $fileName, 'public');
            }
            Excel::import(new PointHistoryImport($platform_id, $userData->id), $file);
            $totalData = DB::table('awb_tmp_point_history')
                ->where('platform_id', '=', $platform_id)
                ->count();
            If($configSendEmail == 'TRUE' && $totalData > 0){
                $details = [
                    'platform_id' => $platform_id,
                ];
                SendEmailAwbPointHistory::dispatch($details);
            }
            return response()->json([
                'data' => $totalData,
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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();

        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');

        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'user_created'=> $user_id,
            'platform_id'=> $platform_id
        ));
        try {
            $data = DB_global::cz_insert($this->table_name,$_arrayData,false);
            return response()->json([
                'data' => true,
                'message' => 'data insert success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function UpdateData(Request $request)
    {
        $id         = $request->input('id');
        $user_id    = $request->input('user_id');
        $all        = $request->except('user_account','id','slider_file','user_id');

       
        $all = array_merge($all,
        array(
            'user_modified'=> $user_id,
            'date_modified' => DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name,'id',$id,$all);

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
            $query = DB::table($this->table_name .' as a')
                ->where(DB::RAW('md5(a.id)'), '=', $id)
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
