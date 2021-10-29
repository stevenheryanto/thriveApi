<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use App\Exports\AWB\UnmatchedUserExport;
use App\Imports\AWB\UserInfoImport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_users_info;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;

class UserInfoController extends Controller
{
    protected $table_name = 'awb_users_info';

    public function ListData(Request $request)
    {	
        $platform_id = $request->input('platform_id');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        try {
            $query = awb_users_info::selectRaw('awb_users_info.* , ifnull(b.full_name, b.name) as name')
                ->join('users as b', 'b.id', '=', 'awb_users_info.id')
                ->where('awb_users_info.platform_id', '=', $platform_id);

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy(DB::raw('ifnull(b.full_name,b.name)'), 'desc')
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

    public function ExportData(Request $request)
    {	
        /*ExportUnmatchedUserData*/
        $platform_id = $request->input('platform_id');
        $dateStamp =  date('Ymdhms');
        try {
            $folder_name = 'learn/temp/';
            $excelName = 'export_unmatched_user_data_'.$dateStamp. '.xlsx';
            Excel::store(new UnmatchedUserExport($platform_id),  $folder_name.$excelName);
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            return Storage::get($path, 200, $headers);
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
        try {
            $totalData = 0;
            if($request->hasFile('userInfo_file'))
            {
                $file = $request->file('userInfo_file');
                $fileName = $userData->account. '_' .$file->getClientOriginalName();
                $fileName = DB_global::cleanFileName($fileName);
                Storage::putFileAs('learn/user_info', $file, $fileName, 'public');
            }
            awb_users_info::where('platform_id', '=', $platform_id)->delete();
            Excel::import(new UserInfoImport($userData->id, $platform_id), $file);
            $totalData = awb_users_info::where('platform_id', '=', $platform_id)->count();

            return response()->json([
                'data' => $totalData,
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
