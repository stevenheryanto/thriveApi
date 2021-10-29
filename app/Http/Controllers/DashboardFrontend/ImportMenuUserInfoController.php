<?php

namespace App\Http\Controllers\DashboardFrontend;
use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

//use Maatwebsite\Excel\Excel;


use App\Imports\ImportUsers;
use Maatwebsite\Excel\Facades\Excel;


class ImportMenuUserInfoController extends Controller
{
    protected $table_name   = 'menu_user_info';
    protected $folder_name  = 'test_excel';

    public function ListData(Request $request)
    {
        $where      = $request->input('str_where');

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        //$platform_id = $request->input('platform_id');


       $sql = "
            select 
                users.*,
                menu_users_info.group_grade,
                menu_users_info.group_function,
                menu_users_info.group_basetown_location,
                menu_users_info.generation
            from 
                users,
                menu_users_info
            where 
                users.status_active    = 1
                and  users.id   = menu_users_info.id
            order by 
                users.id
            asc";

       $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
       if ($category != "COUNT" && $export == false)
       {
            $sql = $sql . " LIMIT  :offset,:limit ";
            //code...
            $param = array(
                'limit'=>$limit,
                'offset'=>$offset
            );
       }else{
            //code...
            $param = array(
                
            );
       }

       try {

            #print $sql;
            $data = DB_global::cz_result_set($sql,$param,false,$category);
            
            return response()->json([
                'data'      => $data,
                'message'   => 'success'
            ]);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
   }

  

    public function ImportData(Request $request)
    {
        Excel::import(new ImportUsers, request()->file('excel_file'));
        return response()->json([
            'message' => 'success'
        ]);
    }

}
