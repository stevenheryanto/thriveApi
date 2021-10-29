<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_trn_category;

class CategoryController extends Controller
{
    protected $table_name   = 'awb_trn_category';
    protected $folder_name  = 'learn/category';


    public function ListData(Request $request)
    {
        

        $limit          = $request->input('limit');
        $offset         = $request->input('offset');
        $category       = $request->input('category');
        $menuId         = $request->input('menuId');
        $export         = $request->input('export');
        $platform_id    = $request->input('platform_id');
        $menuId         = $request->input('menuId');
        
        try {

            $query = DB::table($this->table_name.' as a')->select(
                'a.*','x.title as menu_title',
                'b.title as section_title',
                'c.name as user_modified', 'b.date_modified'
                )->leftJoin('awb_mst_menu as x', 'x.id', '=', 'a.menu_id')
                ->leftJoin('awb_mst_section as b', 'b.id', '=', 'x.section_id')
                ->leftJoin('users as c', 'c.id', '=', 'a.user_modified')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($menuId), function($query) use($menuId){
                    $query->where('a.menu_id', '=', $menuId);})
                ;

            if($category != "COUNT"){
                $data = $query->when(isset($offset) && isset($limit), function($query) use($offset, $limit){
                        $query->offset($offset)->limit($limit);
                    })->orderBy('a.sort_index', 'asc')
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
    
        $_arrayData = $request->except('user_account','category_image');
        
        if($request->hasFile('category_image'))
        {
            $file       = $request->file('category_image');
            $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName   = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $_arrayData = array_merge($_arrayData,
            array(
                'category_image'        => $fileName
            ));
        }
        unset($_arrayData['user_account'] , $_arrayData['category_image'], $_arrayData['user_id']);

        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert($this->table_name, $_arrayData, false);

            $this->ReSortingIndex($_arrayData['platform_id'],$_arrayData['menu_id']);

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
        $all        = $request->except('user_account','id','category_image','user_id');

        if($request->hasFile('category_image'))
        {
            $file = $request->file('category_image');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['category_image' => $fileName]);
        }

        //var_dump($all);exit();
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name,'id', $id, $all);
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
            $query =  awb_trn_category::select('awb_trn_category.*',
                'x.title as menu_title',
                'b.title as section_title',
                DB::raw( "concat(b.title,' - ' , x.title) as section_menu"),
                'b.date_modified'
                )->leftJoin('awb_mst_menu as x', 'x.id', '=', 'awb_trn_category.menu_id')
                ->leftJoin('awb_mst_section as b', 'b.id', '=', 'x.section_id')
                ->where(DB::RAW('md5(awb_trn_category.id)'), '=', $id);
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

            $catData = $this->GetDataFromId($id);

            $data = DB_global::cz_delete($this->table_name,'id',$id);
            
            $this->ReSortingIndex($catData->platform_id,$catData->menu_id);

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
                ->where('menu_id','=',$menu_id)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $this->ReSortingIndex($platform_id, $menu_id);

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
                ->where('menu_id','=',$menu_id)
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);

            $this->ReSortingIndex($platform_id, $menu_id);

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

	function ReSortingIndex($platform_id, $menu_id)
	{
        DB::statement(DB::raw('set @rownum = 0'));
        DB::table($this->table_name)
            ->where('platform_id', $platform_id)
            ->where('menu_id',$menu_id)
            ->orderBy('sort_index', 'asc')
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum + 1'),
            ]);
	}

    public function ListSectionMenu(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $query = DB::table('awb_mst_menu as a')->select('a.id',
                DB::raw("CASE WHEN b.id = 7 THEN b.title
                    WHEN b.id = 6 THEN a.title 
                    ELSE concat(b.title, ' > ',a.title) 
                    END as description")
                )->leftJoin('awb_mst_section as b', 'a.section_id', '=', 'b.id')
                ->where('a.flag_active', '=', 1)
                ->where('a.platform_id', '=', $platform_id)
                ->whereNotIn('a.id', [23,24,25])
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

    public function ListMenu(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $query = DB::table('awb_mst_menu as a')->select('a.id',
                DB::raw("concat(b.title,' - ' , a.title) as title")
                )->leftJoin('awb_mst_section as b', 'a.section_id', '=', 'b.id')
                ->where('a.flag_active', '=', 1)
                ->where('a.platform_id', '=', $platform_id)
                ->whereIn('a.section_id', [2,3,4,8])
                ->orderBy('b.id')
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

    public function MenuSpecial(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $query = DB::table('awb_trn_category as a')->select('a.title as awb_trn_category_title',
                'a.id as awb_trn_category_id',
                'b.title as awb_mst_menu_title'
                )->join('awb_mst_menu as b', 'a.menu_id', '=', 'b.id')
                ->where('a.flag_active', '=', 1)
                ->where('b.flag_active', '=', 1)
                ->where('a.platform_id', '=', $platform_id)
                ->whereIn('a.menu_id', [36,37,38])
                ->orderBy('b.title');
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

    function GetDataFromId($id)
	{
        $query =  DB::table($this->table_name)
            ->where('id','=',$id);

        $data = $query->first();
        
        return $data;
	}
    
}
