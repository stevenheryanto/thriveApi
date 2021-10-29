<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_slider_category;

class SliderCategoryController extends Controller
{
    protected $table_name = 'awb_mst_slider_category';
    protected $folder_name = 'learn/slider_category';

    public function ListData(Request $request)
    {
        

        $limit          = $request->input('limit');
        $offset         = $request->input('offset');
        $category       = $request->input('category');
        $category_id    = $request->input('categoryId');
        $export         = $request->input('export');
        $platform_id    = $request->input('platform_id');
        $flag_active    = $request->input('flag_active');
        
        $param      = [];

        try {
            $query = awb_mst_slider_category::where('platform_id','=',$platform_id)
                        ->where(DB::RAW('md5(category_id)'),'=',$category_id);

            if ($flag_active){
                $query = $query->where('flag_active','=',$flag_active);
            }

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('flag_active','desc')
                    ->orderBy('sort_index','desc')
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

        $platform_id = $request->input('platform_id');
        $category_id = $request->input('category_id');
        $user_created = $request->input('user_created');

        $file = $request->file('slider_video');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['slider_video'], $_arrayData['user_created']);

        
        $file2      = $request->file('slider_video_mobile');
        $fileName2  = $request->input('user_account'). '_' .$file2->getClientOriginalName();
        $fileName2  = DB_global::cleanFileName($fileName2);
        Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
        unset($_arrayData['user_account'] , $_arrayData['slider_video_mobile'], $_arrayData['user_created']);


        $_arrayData = array_merge($_arrayData,
        array(
            'slider_video'          => $fileName,
            'slider_video_mobile'   => $fileName2,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'user_created'=> $user_created,
            'date_modified'=> DB_global::Global_CurrentDatetime(),
            'user_modified'=> $user_created,
            'platform_id'=> $platform_id,
            'category_id'=> $category_id
        ));
        try {
            $data = DB_global::cz_insert($this->table_name,$_arrayData,false);

            $this->ReSortingIndex($platform_id,$category_id);

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
        $id         = $request->input('id');
        $user_created    = $request->input('user_created');
        $all        = $request->except('user_account','id','slider_video','slider_video_mobile','user_created');

        if($request->hasFile('slider_video'))
        {
            $file = $request->file('slider_video');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['slider_video' => $fileName]);
        }

        if($request->hasFile('slider_video_mobile'))
        {
            $file2 = $request->file('slider_video_mobile');
            $fileName2 = $request->input('user_account'). '_' .$file2->getClientOriginalName();
            // $file2->move($tujuan_upload, $fileName2);
            $fileName2 = DB_global::cleanFileName($fileName2);
            Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
            $all = array_merge($all, ['slider_video_mobile' => $fileName2]);
        }


        $all = array_merge($all,
        array(
            'user_modified'=> $user_created,
            'date_modified' => DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name,'id',$id,$all);

            $subCatSliderData = $this->GetDataFromId($id);
            $this->ReSortingIndex($subCatSliderData->platform_id,$subCatSliderData->category_id);

            return response()->json([
                'data' => true,
                'message' => 'data update success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
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
            $query = DB::table($this->table_name .' as a')->select('a.*')
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
            
            $subCatSliderData = $this->GetDataFromId($id);

            $data = DB_global::cz_delete($this->table_name,'id',$id);
            
            $this->ReSortingIndex($subCatSliderData->platform_id,$subCatSliderData->category_id);

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
        $id             = $request->input('id');
        $sort_index     = $request->input('sort_index');
        $platform_id    = $request->input('platform_id');
        $categoryId     = $request->input('categoryId');
      
        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '=',$sort_index + 1)
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
                'data'      => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function MoveDown(Request $request)
	{
        $id             = $request->input('id');
        $sort_index     = $request->input('sort_index');
        $platform_id    = $request->input('platform_id');
        $categoryId     = $request->input('categoryId');

        try {
           
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '=',$sort_index - 1)
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
            ->where('flag_active',1)
            ->orderBy('flag_active', 'desc')
            ->orderBy('sort_index', 'asc')
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum + 1'),
            ]);

	}

    function GetDataFromId($id)
	{
        $query =  DB::table($this->table_name)
            ->where('id','=',$id);

        $data = $query->first();
        
        return $data;
	}


}