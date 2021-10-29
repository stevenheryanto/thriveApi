<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Exports\PinnedExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class PinnedController extends Controller
{
    public function ListDataTab1(Request $request)
	{	
        $where = $request->input('str_where');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $platform_id = $request->input('platform_id');

        if($where !="")
	 	{
            $str_where = " and (a.id like :id or b.name like :name or b.account like :account )";
            $param = array(
                'id'=> '%'.$where.'%',
                'name'=> '%'.$where.'%',
                'account'=> '%'.$where.'%'
            );
        } else {
            $str_where = "";
            $param = [];
        }
        $sql = "select b.name,b.account,a.* 
				from user_post a left join
					users b on a.user_created = b.id
                where b.id is not null 
                and a.platform_id = :platform_id
                $str_where order by a.id desc";

		$offset = ((isset($offset) && $offset <> "") ? $offset : 0);	
		if ($category != "COUNT" && $export == false)
		{
            $sql = $sql . " LIMIT  :offset, :limit ";
            $param = array_merge($param,
            array(
                'platform_id'=>$platform_id,
                'limit'=>$limit,
                'offset'=>$offset,
            ));
		} else {
            $param = array_merge($param,
            array(
                'platform_id'=>$platform_id
            ));
        }			
        try {
            /*echo $sql;*/
            $data = DB_global::cz_result_set($sql,$param,false, $category);

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

    public function ListDataTab2(Request $request)
	{	
        $where = $request->input('str_where');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $platform_id = $request->input('platform_id');

        if($where !="")
	 	{
            $str_where = " and (a.id like :id or b.name like :name or b.account like :account )";
            $param = array(
                'id'=> '%'.$where.'%',
                'name'=> '%'.$where.'%',
                'account'=> '%'.$where.'%'
            );
        } else {
            $str_where = "";
            $param = [];
        }
		
		$sql = "
			select b.name,b.account,a.* 
				from user_post a left join
					users b on a.user_created = b.id
                where b.id is not null and a.pinned_flag = 1
                and a.platform_id = :platform_id
				$str_where 
			order by a.id desc";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);	
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT  :offset, :limit ";
            $param = array_merge($param,
            array(
                'platform_id'=>$platform_id,
                'limit'=>$limit,
                'offset'=>$offset,
            ));
        } else {
            $param = array_merge($param,
            array(
                'platform_id'=>$platform_id
            ));
        }
        try {

            /*echo $sql;*/
            $data = DB_global::cz_result_set($sql,$param,false, $category);

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

	public function FlagAsPinned(Request $request)
	{
        $id = $request->input('md5ID');

		$sql = "update user_post set pinned_flag = 1 where md5(id) = ? ";
        try {
            $data = DB_global::cz_execute_query($sql, [$id]);
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

	public function RevokePinned(Request $request)
	{
        $id = $request->input('md5ID');

		$sql = "update user_post set pinned_flag = 0 where md5(id) = ? ";
        try {
            $data = DB_global::cz_execute_query($sql, [$id]);
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
        $tabtype = $request->input('tabtype');
        $where = $request->input('where');
        $dateStamp =  date('Ymdhms');

        try {
            $folder_name = 'recognition/temp/';
            $excelName = 'admin_pinned_'.$dateStamp. '.xlsx';
        
            // Excel::store(new PinnedExport($platform_id, $tabtype, $where), $excelName);
            // $path = Storage::path($excelName);            
            Excel::store(new PinnedExport($platform_id, $tabtype, $where), $folder_name.$excelName);
            $path = Storage::path($folder_name.$excelName);            
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            // return response()->download($path, $excelName, $headers)->deleteFileAfterSend(false);
            return Storage::get($path, 200, $headers);
            
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
