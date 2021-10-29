<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_trn_workshop_sharing;

class WorkshopSharingController extends Controller
{
    protected $table_name   = 'awb_trn_workshop_sharing';
    protected $folder_name  = 'learn/workshop';

    public function ListData(Request $request)
    {
        $where          = $request->input('str_where');
        $limit          = $request->input('limit');
        $offset         = $request->input('offset');
        $category       = $request->input('category');
        $categoryId       = $request->input('categoryId');
        $sub_category_type       = $request->input('sub_category_type');
        $export         = $request->input('export');
        $platform_id    = $request->input('platform_id');

       try {
            $query = DB::table('awb_trn_workshop_sharing as a')->select('a.*',
                'b.title as awb_trn_category_title'
                )->join('awb_trn_category as b', 'a.category_id', '=', 'b.id')
                ->where('a.platform_id', '=', $platform_id)
            ;
            
            if(isset($categoryId)){
                $query = $query->where(DB::RAW('md5(a.category_id)'), '=', $categoryId);
            } 

            if(isset($sub_category_type)){
                $query = $query->where('a.sub_category_type', '=', $sub_category_type);
            } 

            #print $sql;
            // $data = DB_global::cz_result_set($sql,$param,false,$category);
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('a.title')
                    ->get();
            } else {
                $data = $query->count();
            }

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();


        $file = $request->file('workshop_image');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['workshop_image'], $_arrayData['user_id']);

        $file2      = $request->file('workshop_preview_image');
        $fileName2  = $request->input('user_account'). '_' .$file2->getClientOriginalName();
        $fileName2  = DB_global::cleanFileName($fileName2);
        Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
        unset($_arrayData['user_account'] , $_arrayData['workshop_preview_image']);

        $_arrayData = array_merge($_arrayData,
        array(
            'workshop_image' => $fileName,
            'workshop_preview_image' => $fileName2,
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
        $all    = $request->except('id','user_account','workshop_preview','workshop_preview_image');


        $_arrayData = $request->input();

        if($request->hasFile('workshop_preview'))
        {
            $file       = $request->file('workshop_preview');
            $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName   = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['workshop_preview' => $fileName]);
        }

        if($request->hasFile('workshop_preview_image'))
        {
            $file       = $request->file('workshop_preview_image');
            $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName   = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['workshop_preview_image' => $fileName]);
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
