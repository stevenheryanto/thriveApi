<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_trn_article;
use App\Models\AWB\awb_mst_section;
use App\Models\AWB\awb_trn_article_specific_user;

class ArticleController extends Controller
{
    protected $table_name = 'awb_trn_article';
    protected $folder_name = 'learn/article';

    public function ListData(Request $request)
    {
        

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $menu_id     = $request->input('menuId');
        $keyword    = $request->input('keyword');
        $sort_by    = $request->input('sortBy');
        $export     = $request->input('export');
        $category_id = $request->input('categoryId');
        $sub_category_id = $request->input('subCategoryId');
        $section_id = $request->input('sectionId');
        $platform_id     = $request->input('platform_id');

        try {
            $query = DB::table($this->table_name .' as a')->select('a.*',
                'x.title as menu_title',
                'b.title as section_title',
                'b.id as section_id',
                'y.title as category_title',
                // 'c.name as user_modified',
                'b.date_modified',
                )->leftJoin('awb_trn_category as y', 'a.category_id', '=', 'y.id')
                ->leftJoin('awb_mst_menu as x', 'y.menu_id', '=', 'x.id')
                ->leftJoin('awb_mst_section as b', 'x.section_id', '=', 'b.id')
                // ->leftJoin('users as c', 'a.user_modified', '=', 'c.id')
                ->where('a.platform_id', '=', $platform_id)
                ;

            if(isset($menu_id)){
                $query = $query->where('x.id', '=', $menu_id);
            }   

            if(isset($section_id)){
                $query = $query->where('b.id', '=', $section_id);
            } 
            
            if(isset($keyword)){
                $query = $query->where(function($query2) use($keyword) {
                    $query2->where('a.article_id','=',$keyword)
                        ->orWhere('a.title','LIKE','%'.$keyword.'%')
                        ->orWhere('a.description','LIKE','%'.$keyword.'%')
                        ->orWhere('x.title','LIKE','%'.$keyword.'%')
                        ->orWhere('y.title','LIKE','%'.$keyword.'%');
                });
            } 

            switch($sort_by)
            {
                case 'last_modified':
                    $query=$query-> orderBy('a.date_modified', 'desc');
                    break;
                case 'menu':
                    $query=$query-> orderBy('x.title', 'asc');
                    break;
                case 'section':
                    $query=$query-> orderBy('b.title', 'asc');
                    break;
                case 'article':
                    $query=$query-> orderBy('a.title', 'asc');
                    break;
                case 'of_the_month':
                    $query=$query-> orderBy('a.flag_article_of_the_month', 'desc')
                        ->orderBy('a.sub_category_id', 'asc');
                    break;
                case 'pinned_article':
                    $query=$query-> orderBy('a.flag_pinned', 'desc')
                        ->orderBy('a.sort_index', 'asc')
                        ->orderBy('a.id', 'desc');
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

                // for pinned article, maximum row count is 8 (or depend on limit)
                if($sort_by == 'pinned_article'){
                    if ($data>$limit){
                        $data=$limit;
                    }
                }

            }

            return response()->json([
                'data' => $data,
                'message' => 'success',
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

        $file       = $request->file('article_image');
        $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName   = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['article_image'], $_arrayData['userSpecific']);

        $file2      = $request->file('article_preview_image');
        $fileName2  = $request->input('user_account'). '_' .$file2->getClientOriginalName();
        $fileName2  = DB_global::cleanFileName($fileName2);
        Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
        unset($_arrayData['user_account'] , $_arrayData['article_preview_image']);

        $_arrayData = array_merge($_arrayData,
            array(
                'article_image'         => $fileName,
                'article_preview_image' => $fileName2,
                'date_created'  => DB_global::Global_CurrentDatetime(),
                'date_modified' => DB_global::Global_CurrentDatetime()
            ));
        try {
            // $data = DB_global::cz_insert($this->table_name, $_arrayData, false);
            $data = DB::table($this->table_name)->insertGetId($_arrayData);

            // insert Specific User
            $userSpecificArray = json_decode($request->input('userSpecific'));
            if (isset($userSpecificArray)){
                $user_created= $_arrayData['user_created'];
                $platform_id=$_arrayData['platform_id'];
                $article_id = $data;
                $this->InsertSpecificUser($article_id, $userSpecificArray, $user_created, $platform_id);
            }

            return response()->json([
                'data' => true,
                'message' => 'data insert success',
                'data_return' => $data
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
        $all = $request->except('id','user_account','article_image','article_preview_image', 'userSpecific');
        
        if($request->hasFile('article_image'))
        {
            $file = $request->file('article_image');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['article_image' => $fileName]);
        }

        if($request->hasFile('article_preview_image'))
        {
            $file2 = $request->file('article_preview_image');
            $fileName2 = $request->input('user_account'). '_' .$file2->getClientOriginalName();
            // $file2->move($tujuan_upload, $fileName2);
            $fileName2 = DB_global::cleanFileName($fileName2);
            Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
            $all = array_merge($all, ['article_preview_image' => $fileName2]);
        }

        //var_dump($all);exit();
        $all = array_merge($all, 
            [
                'date_modified' => DB_global::Global_CurrentDatetime()
            ]);
        try {
            $data = DB_global::cz_update($this->table_name,'id', $id, $all);

            // insert Specific User

            $userSpecificArray = json_decode($request->input('userSpecific'));
            if (isset($userSpecificArray)){
                $user_created= $all['user_modified'];
                $platform_id=$all['platform_id'];
                $article_id = $id;
                $this->InsertSpecificUser($article_id, $userSpecificArray, $user_created, $platform_id);
            }

            return response()->json([
                'data' => $data,
                'message' => 'data update success',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data update failed: '.$th,
                'userSpecificArray' => $userSpecificArray,
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
            $query =  awb_trn_article::select('awb_trn_article.*',
            'x.title as menu_title',
            'b.title as section_title',
            DB::raw( "concat(b.title,' - ' , x.title,' - ' , y.title) as category_menu"),
            'b.date_modified'
            )->leftJoin('awb_trn_category as y', 'y.id', '=', 'awb_trn_article.category_id')
            ->leftJoin('awb_mst_menu as x', 'x.id', '=', 'y.menu_id')
            ->leftJoin('awb_mst_section as b', 'b.id', '=', 'x.section_id')
            ->limit(1)
            ->where(DB::RAW('md5(awb_trn_article.id)'), '=', $id);

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

            $article_id = $id;
            $userSpecificArray = [];
            $user_created='';
            $platform_id='';
            $this->InsertSpecificUser($article_id, $userSpecificArray, $user_created, $platform_id);

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

    function InsertSpecificUser($article_id, $userSpecificArray, $user_created, $platform_id)
    {

        $totalShareSpesificUser = 0;
        // update flag to 0
        $update1 = DB::table('awb_trn_article_spesific_user')
            ->where('trn_article_id', '=', $article_id)
            ->update(['flag_active' => 0]);

        // loop user specific array
        foreach($userSpecificArray as $userSpecificItem){
            $totalShareSpesificUser+=1;
            // check if exist
            $users = DB::table('awb_trn_article_spesific_user')
                ->select('id')
                ->where('trn_article_id','=',$article_id)
                ->where('user_id','=',$userSpecificItem->value)
                ->get();
            
            if (count($users)>0){
                // update flag if exist
            
                $update2 = DB::table('awb_trn_article_spesific_user')
                    ->where('id','=',$users[0]->id)
                    ->update(['flag_active' => 1]);
            }else{
                // insert if not exist
                $arrayInsert=array(
                    'trn_article_id'=> $article_id,
                    'user_id'=> $userSpecificItem->value,
                    'user_created'=>$user_created,
                    'date_created'=>DB_global::Global_CurrentDatetime(),
                    'platform_id'=>$platform_id
                );	

                $insert1 = DB::table('awb_trn_article_spesific_user')->insert($arrayInsert);
            }
        }
        //delete data with flag 0
        $delete1 = DB::table('awb_trn_article_spesific_user')
            ->where('trn_article_id','=',$article_id)
            ->where('flag_active','=',0)
            ->delete();

        $flagShareSpesificUser = ($totalShareSpesificUser > 0 ? 1 : 0);
        //update flag and count of specific user in awb_trn_article
        $update3 = DB::table('awb_trn_article')
            ->where('id', $article_id)
            ->update(['total_show_spesific_user' => $totalShareSpesificUser
                , 'flag_show_spesific_user' => $flagShareSpesificUser]);
    }
    
    public function ListDataArticleByCategory(Request $request)
    {
        

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $sort_by    = $request->input('sortBy');
        $export     = $request->input('export');
        $platform_id     = $request->input('platform_id');
        $where = $request->input('str_where');
        $subCategoryId = $request->input('subCategoryId');
        $categoryId = $request->input('categoryId');
           
        try {

            $query = DB::table($this->table_name .' as a')->select('a.*',
                'x.title as menu_title',
                'b.title as section_title',
                'b.id as section_id',
                'y.title as category_title',
                'd.title as sub_category_title'
                )->leftJoin('awb_trn_category as y', 'a.category_id', '=', 'y.id')
                ->leftJoin('awb_mst_menu as x', 'y.menu_id', '=', 'x.id')
                ->leftJoin('awb_mst_section as b', 'x.section_id', '=', 'b.id')
                ->leftJoin('awb_trn_sub_category as d', 'a.sub_category_id', '=', 'd.id')
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', '1')
                ;

            if(isset($where)){
                $query = $query->where('a.title', 'LIKE','%'.$where.'%');
            }   

            if(isset($subCategoryId)){
                $query = $query->where('a.sub_category_id', '=', $subCategoryId);
            } 
            
            if(isset($categoryId)){
                $query = $query->where('a.category_id','=',$categoryId);
            } 

            switch($sort_by)
            {
                case 'last_modified':
                    $query=$query-> orderBy('a.date_modified', 'desc');
                    break;
                case 'menu':
                    $query=$query-> orderBy('x.title', 'asc');
                    break;
                case 'section':
                    $query=$query-> orderBy('b.title', 'asc');
                    break;
                case 'article':
                    $query=$query-> orderBy('a.title', 'asc');
                    break;
                case 'of_the_month':
                    $query=$query-> orderBy('a.flag_article_of_the_month', 'desc')
                        ->orderBy('a.sub_category_id', 'asc');
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

    public function ListSectionPinnedArticle(Request $request)
	{
        $platform_id = $request->input('platform_id');
		try {
            $query = awb_mst_section::select('id','title as description')
                ->whereIn('id', [2,3,4])
                ->where('flag_active', '=', 1)
                ->where('platform_id', '=', $platform_id)
                ->orderBy('title');
            $data = $query->get();
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

    public function MoveUp(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');
        $menu_id = $request->input('menu_id');

        try {
           
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '>=',$sort_index - 1)
                ->where('sort_index','<>',$sort_index)
                ->where('flag_pinned','=',1)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->where('flag_pinned','=',1)
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $this->ReSortingIndex($platform_id);

            return response()->json([
                'data' => $data,
                'data2' => $data2,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function MoveDown(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');
        $menu_id = $request->input('menu_id');

        try {
           
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '<=',$sort_index + 1)
                ->where('sort_index','<>',$sort_index)
                ->where('flag_pinned','=',1)
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->where('flag_pinned','=',1)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);
            
            $this->ReSortingIndex($platform_id);

            return response()->json([
                'data' => $data,
                'data2' => $data2,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	function ReSortingIndex($platform_id)
	{
        DB::statement(DB::raw('set @rownum = 0'));
        DB::table($this->table_name)
            ->where('platform_id', $platform_id)
            ->orderBy('flag_pinned', 'desc')
            ->orderBy('sort_index', 'asc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum + 1'),
            ]);
	}

    public function ListCategory(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $query = DB::table('awb_trn_category as a')->select('a.id',
                DB::raw("CASE WHEN c.id = 6 THEN b.title
                    WHEN c.id = 7 THEN c.title 
                    ELSE concat(c.title, ' - ',b.title,' - ',a.title) 
                    END as title")
                )->leftJoin('awb_mst_menu as b', 'a.menu_id', '=', 'b.id')
                ->leftJoin('awb_mst_section as c', 'b.section_id', '=', 'c.id')
                ->where('a.flag_active', '=', 1)
                ->where('a.platform_id', '=', $platform_id)
                ->whereIn('c.id', [2,3,4,6,7,8])
                ->orderBy('b.title')
                ->orderBy('a.title');
            $data = $query->get();
            return response()->json([
                'data'      => $data,
                'message'   => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function GetDetailSpecificUser(Request $request)
	{
        $id = $request->input('md5ID');
		        
        try {
            $query = DB::table('awb_trn_article_spesific_user as a')->select('a.*',
                'b.id','b.account','b.name')
                ->leftJoin('users as b', 'a.user_id', '=', 'b.id')
                ->where(DB::RAW('md5(a.trn_article_id)'), '=', $id);
                
            $data = $query->get();

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

}