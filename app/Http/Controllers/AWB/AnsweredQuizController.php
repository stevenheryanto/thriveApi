<?php

namespace App\Http\Controllers\AWB;


use DB_global;
use App\Exports\AWB\AnsweredQuizExport;
use App\Http\Controllers\Controller;
use App\Models\AWB\awb_mst_config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AnsweredQuizController extends Controller{

    public function ListData(Request $request)
	{	
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $filter_category = $request->input('filter_category');
        $platform_id = $request->input('platform_id');
        $category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
        try {
            $query = DB::table('awb_trn_quiz_user as a')->select(
                'a.id', 'b.question', 'b.question_ind', 'b.answer_mode', 'a.point', 'a.date_modified',
				'c.id as user_id', 'c.account', 
                DB::RAW('ifnull(c.name,c.full_name) as user_name'),
				DB::RAW('case when 1 = a.answer_choice_idx then b.choice_1
					when 2 = a.answer_choice_idx then b.choice_2
					when 3 = a.answer_choice_idx then b.choice_3
					when 4 = a.answer_choice_idx then b.choice_4 end as quiz_answer'),
					'b.choice_1',
					'b.choice_2',
					'b.choice_3',
					'b.choice_4',
					'b.answer_choice_mode_3',
				DB::RAW('case when 1 = a.answer_flag_idx then b.choice_1
					when 2 = a.answer_flag_idx then b.choice_2
					when 3 = a.answer_flag_idx then b.choice_3
					when 4 = a.answer_flag_idx then b.choice_4 end as user_answer'),
				DB::RAW('case when a.answer_result = 1 then "Correct" else "Wrong" end as quiz_result'),
				DB::RAW('(SELECT title FROM awb_trn_article d WHERE b.trn_article_id = d.id) as article_title')
                )
                ->leftJoin('awb_trn_quiz as b', 'b.id', '=', 'a.trn_quiz_id')
                ->join('users as c', 'c.id', '=', 'a.user_modified')
                ->when($filter_category == 'iqos', function($query) use($category_iqos_delivery){
                        $query->join('awb_trn_article as e', function($join) use($category_iqos_delivery){
                            $join->on('e.id', '=', 'b.trn_article_id')
                                ->on('e.category_id','=', DB::raw($category_iqos_delivery));
                        });
                })
                ->where('c.status_active', '=', '1')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($filter_period_from, $filter_period_to),
                    function ($query) use ($filter_period_from, $filter_period_to) {
                    $query->whereBetween(DB::raw('convert(a.date_modified,date)'), [$filter_period_from, $filter_period_to]);
                });
                    
            if($category != "COUNT"){
                $data = $query->when(isset($offset, $limit), function($query) use($offset, $limit){
                        $query->offset($offset)->limit($limit);
                    })->orderBy('a.id', 'desc')
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

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::cz_delete('awb_trn_submit_idea','md5(id)',$id);
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

    public function ExportData(Request $request)
	{	
        $platform_id = $request->input('platform_id');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $filter_category = $request->input('filter_category');
        $category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
        try {
            ini_set('memory_limit','512M');
            return new AnsweredQuizExport($platform_id, $filter_period_from, $filter_period_to, $filter_category, $category_iqos_delivery);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
