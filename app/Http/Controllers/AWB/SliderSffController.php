<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_mst_slider_sff;

class SliderSffController extends Controller
{
    
    protected $folder_name = 'learn/slider_sff';
    protected $table_name = 'awb_mst_slider_sff';

    
    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');
        $status_active = $request->input('status_active');

        try {

            $query = awb_mst_slider_sff::where('platform_id','=',$platform_id);

            if ($status_active){
                $query = $query->where('flag_active','=',$status_active);
            }

            if($category != "COUNT"){
                if (isset($limit)){
                    $query = $query->limit($limit);
                }
                $data = $query->offset($offset)
                    ->orderBy('flag_active','desc')
                    ->orderBy('seqnum','asc')
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
        
        $file       = $request->file('slider_image');
        $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName   = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['slider_image'], $_arrayData['user_id']);

        $_arrayData = array_merge($_arrayData,
        array(
            'slider_image' => $fileName,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified' => DB_global::Global_CurrentDatetime(),
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
        $all        = $request->except('user_modified','user_account','id','slider_image','user_id');

        if($request->hasFile('slider_image'))
        {
            $file = $request->file('slider_image');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['slider_image' => $fileName]);
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
        $seqnum     = $request->input('seqnum');
        $platform_id    = $request->input('platform_id');
      
        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('seqnum', '=',$seqnum - 1)
                ->update([
                    'seqnum' => DB::raw('seqnum + 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'seqnum' => DB::raw('seqnum - 1'),
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
        $seqnum     = $request->input('seqnum');
        $platform_id    = $request->input('platform_id');

        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('seqnum', '=',$seqnum + 1)
                ->update([
                    'seqnum' => DB::raw('seqnum - 1'),
            ]);

            $data2 = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('id', '=',$id)
                ->update([
                    'seqnum' => DB::raw('seqnum + 1'),
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
        DB::statement(DB::raw('set @rownum = 0'));
        DB::table($this->table_name)
            ->where('platform_id', $platform_id)
            ->where('flag_active','=',1)
            ->orderBy('flag_active', 'desc')
            ->orderBy('seqnum', 'asc')
            ->orderBy('date_modified', 'desc')
            ->update([
                'seqnum' => DB::raw('@rownum := @rownum + 1'),
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
