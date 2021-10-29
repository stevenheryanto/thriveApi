<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_mst_slider;

class SliderController extends Controller
{
    
    protected $folder_name = 'learn/slider';
    protected $table_name = 'awb_mst_slider';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $platform_id = $request->input('platform_id');

        $status_active = $request->input('status_active');

        $param = [];

        try {
            $query = awb_mst_slider::where('platform_id','=',$platform_id);

            if ($status_active){
                $query = $query->where('flag_active','=',$status_active);
            }

            if($category != "COUNT"){
                if (isset($limit)){
                    $query = $query->limit($limit);
                }
                $data = $query->offset($offset)
                    ->orderBy('flag_active','desc')
                    ->orderBy('sort_index','desc')
                    ->orderBy('date_modified','desc')
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
        $user_id = $request->input('user_id');
        
        if($request->hasFile('slider_video'))
        {
            $file = $request->file('slider_video');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $_arrayData = array_merge($_arrayData,['slider_video' => $fileName]);
        }
        unset($_arrayData['user_account'] , $_arrayData['user_id']);
        
        if($request->hasFile('slider_video_mobile'))
        {
            $file2      = $request->file('slider_video_mobile');
            $fileName2  = $request->input('user_account'). '_' .$file2->getClientOriginalName();
            $fileName2  = DB_global::cleanFileName($fileName2);
            Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
            $_arrayData = array_merge($_arrayData,['slider_video_mobile' => $fileName2]);
        }
        unset($_arrayData['user_account'] , $_arrayData['user_created']);

        $_arrayData = array_merge($_arrayData,
        array(
            // 'slider_video' => $fileName,
            // 'slider_video_mobile' => $fileName2,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime(),
            'user_created'=> $user_id,
            'platform_id'=> $platform_id
        ));
        try {
            $data = DB_global::cz_insert($this->table_name,$_arrayData,false);

            $this->ReSortingIndex($platform_id);

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
        $user_id    = $request->input('user_id');
        $all        = $request->except('user_account','id','slider_video','slider_video_mobile','user_id');

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
            $file = $request->file('slider_video_mobile');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['slider_video_mobile' => $fileName]);
        }
        $all = array_merge($all,
        array(
            'user_modified'=> $user_id,
            'date_modified' => DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name,'id',$id,$all);

            $platformId = $this->GetPlatformIdFromId($id);
            $this->ReSortingIndex($platformId);

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
            $platformId = $this->GetPlatformIdFromId($id);

            $data = DB_global::cz_delete($this->table_name,'id',$id);
            
            $this->ReSortingIndex($platformId);

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
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);
            
            $this->ReSortingIndex($platform_id);
            
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

        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '=',$sort_index - 1)
                ->update([
                    'sort_index' => DB::raw('sort_index + 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'sort_index' => DB::raw('sort_index - 1'),
            ]);

            $this->ReSortingIndex($platform_id);

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

    function ReSortingIndex($platform_id)
	{
        $query = awb_mst_slider::where('platform_id','=',$platform_id)
                ->where('flag_active','=',1);

        $count = $query->count();
        $count = $count + 1;

        DB::statement(DB::raw('set @rownum = '.$count));
        DB::table($this->table_name)
            ->where('platform_id', $platform_id)
            ->where('flag_active','=',1)
            ->orderBy('flag_active', 'desc')
            ->orderBy('sort_index', 'desc')
            ->orderBy('date_modified', 'desc')
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum - 1'),
            ]);

	}

    function GetPlatformIdFromId($id)
	{
        $query =  DB::table($this->table_name)
            ->select('platform_id')
            ->where('id','=',$id);

        $data = $query->first();
        $platform_id = $data->platform_id;
        return $platform_id;
	}

}
