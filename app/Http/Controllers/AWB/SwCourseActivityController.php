<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\sw_mst_exam;

class SwCourseActivityController extends Controller
{
    protected $table_name = 'sw_mst_exam';
    

    public function ListData(Request $request)
    {
        

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $sort_by    = $request->input('shortBy');
        $export     = $request->input('export');
        $platform_id     = $request->input('platform_id');


        $where = $request->input('str_where');
        $str_where 					= "";
        $param = [];

		$sql = "

            SELECT 
                a.id
                ,b.name_eng AS submod_name
                ,date_format(a.date_modified, '%d-%b-%Y') AS date_submit
                ,c.id AS user_id
                ,c.account
                ,ifnull(c.name, c.full_name) AS user_name
            FROM 
                sw_trn_submod a
                LEFT JOIN sw_mst_submod b ON a.submod_id = b.id
                LEFT JOIN users c ON a.emplid = c.id
            WHERE 
                c.status_active = 1
                AND a.platform_id = :platform_id
            ORDER BY 
                a.submod_id ASC,a.date_modified DESC
        
			";
     
            $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
            if ($category != "COUNT" && $export == false)
            {
                $sql = $sql . " LIMIT  :offset, :limit  ";
                //code...
                $param =  array_merge($param,
                    array(
                        'limit'=>$limit,
                        'offset'=>$offset,
                        'platform_id'=>$platform_id
                    )
                );
            }else{
                $param =  array_merge($param,
                    array(
                        'platform_id'=>$platform_id
                    )
                );
           }    

        try {

            $data = DB_global::cz_result_set($sql,$param,false,$category);
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
