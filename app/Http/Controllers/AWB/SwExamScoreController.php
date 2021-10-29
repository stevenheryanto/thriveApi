<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\sw_mst_exam;

class SwExamScoreController extends Controller
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
                ,b.name AS exam_name
                ,b.pass_grade
                ,date_format(a.date_created, '%d-%b-%Y') AS date_submit
                ,a.score
                ,c.id AS user_id
                ,c.account
                ,ifnull(c.name, c.full_name) AS user_name
                ,e.name_eng as curriculum_name
            FROM 
                sw_trn_exam a
                LEFT JOIN sw_mst_exam b ON a.id_exam = b.id
                LEFT JOIN users c ON a.user_created = c.id
                LEFT JOIN sw_mst_curriculum e on e.exam_mid = a.id_exam or e.exam_final = a.id_exam
            WHERE 
                c.status_active = 1 
                AND a.platform_id = :platform_id
           
        
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
