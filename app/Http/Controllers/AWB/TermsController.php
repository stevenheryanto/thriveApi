<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TermsController extends Controller
{
    protected $table_name = 'awb_mst_terms';
    
    public function ListData(Request $request)
    {
        /* ListTerms use flag_active */
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $flag_active = $request->input('flag_active');

        try {
            $query = DB::table('awb_mst_terms as a')->select(
                DB::raw('md5(a.id) as md5id'),
                'a.*', 'b.name as user_modified', 'b.date_modified'
                )->leftJoin('users as b', 'b.id', '=', 'a.user_modified')
                ->where('a.platform_id', '=', $platform_id)
                ->when(isset($flag_active), function($query) use($flag_active){
                    $query->where('flag_active', '=', $flag_active);
                });
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
        $platform_id = $request->input('platform_id');
        $_arrayData = $request->input();
        $user_id = $request->input('user_id');        
        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'=> DB_global::Global_CurrentDatetime(),
            // 'user_created'=> $user_id,
            'date_modified'=> DB_global::Global_CurrentDatetime(),
            // 'user_modified'=> $user_id
        ));
        try {
            $data = DB_global::cz_insert($this->table_name, $_arrayData);

            $this->ReSortingIndex($platform_id);

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
        $id         = $request->input('id');
        $user_id    = $request->input('user_id');
        $all        = $request->except('user_account','id','user_id');
        $all = array_merge($all,
        array(
            // 'user_modified'=> $user_id,
            'date_modified' => DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name, 'id', $id, $all);

            $platformId = $this->GetPlatformIdFromId($id);
            $this->ReSortingIndex($platformId);

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
            $query = DB::table($this->table_name .' as a')
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

            $data = DB_global::cz_delete($this->table_name, 'id', $id);
            
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
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');

        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '>=',$sort_index - 1)
                ->where('sort_index','<>',$sort_index)
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

        try {
            
            $data = DB::table($this->table_name)
                ->where('platform_id', $platform_id)
                ->where('sort_index', '<=',$sort_index + 1)
                ->where('sort_index','<>',$sort_index)
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
            ->orderBy('sort_index', 'asc')
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum + 1'),
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
