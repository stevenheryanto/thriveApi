<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_user_sub_article;
use App\Models\AWB\awb_mst_config;
use App\Jobs\SendEmailAwbUpdateArticle;

class SubmittedArticleController extends Controller
{
    protected $table_name = 'awb_user_sub_article';

    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');
        $keyword    = $request->input('keyword');
        $sort_by    = $request->input('sortBy');

        try {
            $query = DB::table($this->table_name .' as a')->select('a.*',
                'c.name as user_modified'
                )->leftJoin('users as c', 'a.user_modified', '=', 'c.id')
                ->where('a.platform_id', '=', $platform_id)
                ;

            if(isset($keyword)){
                $query = $query->where(function($query2) use($keyword) {
                    $query2->where('a.id','=',$keyword)
                        ->orWhere('a.title','LIKE','%'.$keyword.'%')
                        ->orWhere('a.description','LIKE','%'.$keyword.'%');     
                });
            } 

            switch($sort_by)
            {
                case 'last_modified':
                    $query=$query-> orderBy('a.date_modified', 'desc');
                    break;
                case 'article':
                    $query=$query-> orderBy('a.title', 'asc');
                    break;
                case 'status':
                    $query=$query-> orderBy('a.status', 'asc');
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
        try {
            if($request->hasFile('article_file'))
            {
                $file = $request->file('article_file');
                $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
                $fileName = DB_global::cleanFileName($fileName);
                Storage::putFileAs('learn/article', $file, $fileName, 'public');
                unset($_arrayData['article_file']); 
                $_arrayData = array_merge($_arrayData, ['article_doc' => $fileName]);
            }
            unset($_arrayData['user_account']); 
            $_arrayData = array_merge($_arrayData,
            array(
                'date_created'=> DB_global::Global_CurrentDatetime(),
                'date_modified'=> DB_global::Global_CurrentDatetime()
            ));
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
        $platform_id = $request->input('platform_id');        
        $id = $request->input('id');        
        $status = $request->input('status');        
        $user_created = $request->input('user_created');        
        $title = $request->input('title');        
        $description = $request->input('description');        
        $all = $request->except('id','user_account');
        $configSendEmail = awb_mst_config::where('_code', '=', 'email_notification')->where('platform_id', '=', $platform_id)->value('value');

        $all = array_merge($all, [
            'date_modified' => DB_global::Global_CurrentDatetime()
        ]);
        try {
            $data = DB_global::cz_update($this->table_name,'id', $id, $all);
            if($configSendEmail == 'TRUE' && ($status == 3 || $status == 4)){
                $details = [
                    'user_created' => $user_created,
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                ];
                SendEmailAwbUpdateArticle::dispatch($details);
            }
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
            $query = DB::table($this->table_name .' as a')->select('a.*',
                'b.name as name_created',
                'c.name as name_modified'
                )->leftJoin('users as b', 'a.user_created', '=', 'b.id')
                ->leftJoin('users as c', 'a.user_modified', '=', 'c.id')
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
