<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_trn_sub_category;

class SubCategoryController extends Controller
{
    protected $table_name = 'awb_trn_sub_category';


    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');
       
        $md5Category = $request->input('md5categoryId');
        $categoryId = $request->input('categoryId');
        
        try {

            $query = DB::table('awb_trn_category as a')->select(
                'a.id as id_category', 'a.title as title_category', 'a.flag_active as flag_active_category',
                'b.id AS id_menu', 'b.title AS title_menu', 'b.flag_active as flag_active_menu',
                'c.id AS id_section', 'c.title AS title_section','c.flag_active as flag_active_section',
                'd.id AS id_sub_category', 'd.title AS title_sub_category','d.flag_active as flag_active_sub_category','d.sort_index'
                )->join('awb_mst_menu as b', 'b.id', '=', 'a.menu_id')
                ->join('awb_mst_section as c', 'c.id', '=', 'b.section_id')
                ->join('awb_trn_sub_category as d', 'a.id', '=', 'd.category_id')
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', '1')
                ->where('b.flag_active', '=', '1')
                ->where('c.flag_active', '=', '1')
                ->when(isset($categoryId), function($query) use($categoryId){
                    $query->where('d.category_id', '=', $categoryId);})
                ->when(isset($md5Category), function($query) use($md5Category){
                    $query->where(DB::RAW('md5(d.category_id)'), '=', $md5Category)
                        ->where('d.flag_active','=','1');})
                ;
            
            if($category != "COUNT"){
                $data = $query->when(isset($offset) && isset($limit), function($query) use($offset, $limit){
                        $query->offset($offset)->limit($limit);
                    })->orderBy('d.sort_index', 'asc')
                    ->orderBy('d.title', 'asc')
                    ->orderBy('c.title', 'asc')
                    ->orderBy('b.title', 'asc')
                    ->orderBy('a.title', 'asc')
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
                'date_created'  => DB_global::Global_CurrentDatetime(),
                'title_ind'     => "",
                'description'     => "",
                'description_ind'     => "",
                'category_image'     => "",
                'date_modified' => DB_global::Global_CurrentDatetime()
            ));
        try {
            $data = DB_global::cz_insert($this->table_name, $_arrayData, false);

            $this->ReSortingIndex($_arrayData['platform_id'],$_arrayData['category_id']);

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
        $all = array_merge($all,
            [
                    'date_modified' => DB_global::Global_CurrentDatetime(),
                    'title_ind'     => "",
                    'description'     => "",
                    'description_ind'     => "",
                    'category_image'     => "",
                ]);
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
            $query = DB::table('awb_trn_category as a')->select(
                'a.id as category_id', 'a.title as title_category', 'a.flag_active as flag_active_category',
                'b.id AS id_menu', 'b.title AS title_menu', 'b.flag_active as flag_active_menu',
                'c.id AS id_section', 'c.title AS title_section','c.flag_active as flag_active_section',
                'd.id', 'd.title','d.flag_active','d.sort_index'
                )->join('awb_mst_menu as b', 'b.id', '=', 'a.menu_id')
                ->join('awb_mst_section as c', 'c.id', '=', 'b.section_id')
                ->join('awb_trn_sub_category as d', 'a.id', '=', 'd.category_id')
                ->where(DB::RAW('md5(d.id)'), '=', $id)
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
            
            $subCatData = $this->GetDataFromId($id);

            $data = DB_global::cz_delete($this->table_name,'id',$id);
            
            $this->ReSortingIndex($subCatData->platform_id,$subCatData->category_id);

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
        $categoryId     = $request->input('categoryId');

        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '>=',$sort_index - 1)
                ->where('sort_index','<>',$sort_index)
                ->where('category_id','=',$categoryId)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $this->ReSortingIndex($platform_id,$categoryId);

            return response()->json([
                'data'      => $data,
                'data2'     => $data2,
                'message'   => 'success'
            ]);
        }
        catch (\Throwable $th) {
            return response()->json([
                'data'      => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function MoveDown(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');
        $categoryId     = $request->input('categoryId');

        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '<=',$sort_index + 1)
                ->where('sort_index','<>',$sort_index)
                ->where('category_id','=',$categoryId)
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);

            $this->ReSortingIndex($platform_id, $categoryId);

            return response()->json([
                'data'      => $data,
                'data2'     => $data2,
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

	function ReSortingIndex($platform_id, $categoryId)
	{
        DB::statement(DB::raw('set @rownum = 0'));
        DB::table($this->table_name)
            ->where('platform_id', $platform_id)
            ->where('category_id',$categoryId)
            ->orderBy('sort_index', 'asc')
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum + 1'),
            ]);

	}

    function ListSectionMenu()
	{
		
        try {
            $query = DB::table('awb_trn_category as a')->select(
                'a.id', 'a.title as title_category',
                'b.id AS id_menu', 'b.title AS title_menu',
                'c.id AS id_section', 'c.title AS title_section'
                )->join('awb_mst_menu as b', 'b.id', '=', 'a.menu_id')
                ->join('awb_mst_section as c', 'c.id', '=', 'b.section_id')
                ->where('a.flag_active', '=', '1')
                ->where('b.flag_active', '=', '1')
                ->where('c.flag_active', '=', '1')
                ->where('a.id', '=', '67')
                ->orderBy('c.title', 'asc')
                ->orderBy('b.title', 'asc')
                ->orderBy('a.title', 'asc')
            ;

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

    function GetDataFromId($id)
	{
        $query =  DB::table($this->table_name)
            ->where('id','=',$id);

        $data = $query->first();
        
        return $data;
	}

}
