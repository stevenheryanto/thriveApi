<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_menu;
use App\Models\AWB\awb_mst_section;

class MenuController extends Controller
{
    protected $table_name   = 'awb_mst_menu';
    protected $folder_name  = 'learn/menu';


    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');

        try {

            $query = DB::table($this->table_name.' as a')->select(
                'a.*', 'b.title as section_title', 'b.date_modified'
                )->leftJoin('awb_mst_section as b', 'b.id', '=', 'a.section_id')
                ->where('a.platform_id', '=', $platform_id)
                ;
            if($category != "COUNT"){
                $data = $query->when(isset($offset) && isset($limit), function($query) use($offset, $limit){
                        $query->offset($offset)->limit($limit);
                    })->orderBy('b.id', 'asc')
                    ->orderBy('b.title', 'asc')
                    ->orderBy('a.sort_index', 'asc')
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

        $file       = $request->file('menu_image');
        $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName   = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'], $_arrayData['menu_image'] , $_arrayData['slider_file']); 

        $_arrayData = array_merge($_arrayData,
        array(
            'menu_image' => $fileName,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert($this->table_name,$_arrayData,false);
            return response()->json([
                'data' => true,
                'message' => 'data insert success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'data insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function UpdateData(Request $request)
    {
        $id     = $request->input('id');        
        $all    = $request->except('id','user_account');


        $_arrayData = $request->input();

        if($request->hasFile('menu_image'))
        {
            $file   = $request->file('menu_image');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['menu_image' => $fileName]);
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
            $query = awb_mst_menu::select('awb_mst_menu.*',
                'b.title as section_title',
                'b.date_modified'
                )->leftJoin('awb_mst_section as b', 'b.id', '=', 'awb_mst_menu.section_id')
                ->where(DB::RAW('md5(awb_mst_menu.id)'), '=', $id);
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

    public function ListSection(Request $request)
	{
        $platform_id = $request->input('platform_id');
		try {
            $query = awb_mst_section::select('id','title')
                ->whereIn('id', [2,3,4,8])
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

    public function MenuSpecial(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $query = awb_mst_menu::select('id','title')
                ->whereIn('id', [36,37,38])
                ->where('flag_active', '=', 1)
                ->where('platform_id', '=', $platform_id)
                ->orderBy('title');
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

}
