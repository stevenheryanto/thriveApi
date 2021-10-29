<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_trn_quiz;
use App\Models\AWB\awb_trn_article;

class QuizController extends Controller
{
    protected $table_name = 'awb_trn_quiz';

    
    
    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');
        $article_id = $request->input('articleId');

        try {
            $query = awb_trn_quiz::where('platform_id','=',$platform_id);
            if(isset($article_id)){
                $query = $query->where(DB::RAW('md5(trn_article_id)'), '=', $article_id);
            } 

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('id')
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
            $this->UpdateArticleFlagQuiz($_arrayData['trn_article_id']);
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
            $this->UpdateArticleFlagQuiz($all['trn_article_id']);
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
            $query =  awb_trn_quiz::select('awb_trn_quiz.*',
            'y.title as article_title',
            )->leftJoin('awb_trn_article as y', 'y.id', '=', 'awb_trn_quiz.trn_article_id')
            ->limit(1)
            ->where(DB::RAW('md5(awb_trn_quiz.id)'), '=', $id);

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

    public function SelectArticle(Request $request)
	{
        $id = $request->input('md5ID');
        
        try {
            $query =  awb_trn_article::select('id',
            'title',
            )
            ->limit(1)
            ->where(DB::RAW('md5(id)'), '=', $id);

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
            //get trn_article_id
            $selectArticleId = DB::table('awb_trn_quiz')
                ->select('trn_article_id')
                ->where('id','=',$id)
                ->get();

            $data = DB_global::cz_delete($this->table_name,'id',$id);

            $this->UpdateArticleFlagQuiz($selectArticleId[0]->trn_article_id);

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

    function UpdateArticleFlagQuiz($article_id)
	{
        $select = awb_trn_quiz::where('trn_article_id','=',$article_id);       
        $count = $select->count();
        
        if ($count>0){
            $flag_quiz=1;
        }else{
            $flag_quiz=0;
        }

        $update1 = DB::table('awb_trn_article')
            ->where('id', '=', $article_id)
            ->update(['flag_quiz' => $flag_quiz]);
	}

}
