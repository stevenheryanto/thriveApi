<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_mst_course;

class CourseController extends Controller
{
    
    protected $folder_name = 'learn/course';
    protected $table_name = 'awb_mst_course';

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id   = $request->input('platform_id');
        $keyword    = $request->input('keyword');
        $sort_by    = $request->input('sortBy');

        try {
            //code...
            $query = DB::table($this->table_name .' as a')->select('a.*',
                'x.title as menu_title',
                'b.title as section_title',
                'y.title as category_title',
                // 'c.name as user_modified',
                'a.date_modified'
                )->leftJoin('awb_trn_category as y', 'a.category_id', '=', 'y.id')
                ->leftJoin('awb_mst_menu as x', 'y.menu_id', '=', 'x.id')
                ->leftJoin('awb_mst_section as b', 'x.section_id', '=', 'b.id')
                // ->leftJoin('users as c', 'a.user_modified', '=', 'c.id')
                ->where('a.platform_id', '=', $platform_id)
                ;

            if(isset($keyword)){
                $query = $query->where(function($query2) use($keyword) {
                    $query2->where('a.id','=',$keyword)
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
                case 'article':
                    $query=$query-> orderBy('a.title', 'asc');
                    break;
                case 'status':
                    $query=$query-> orderBy('a.flag_active', 'desc');
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

        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        
        $file       = $request->file('home_image');
        $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName   = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['home_image'], $_arrayData['user_id']);

        $file2      = $request->file('course_image');
        $fileName2  = $request->input('user_account'). '_' .$file2->getClientOriginalName();
        $fileName2  = DB_global::cleanFileName($fileName2);
        Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
        unset($_arrayData['user_account'] , $_arrayData['course_image'], $_arrayData['user_id']);

        

        $_arrayData = array_merge($_arrayData,
        array(
            'home_image' => $fileName,
            'course_image' => $fileName2,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'user_created'=> $user_id,
            'platform_id'=> $platform_id,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime(),
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
        $id         = $request->input('id');
        $user_id    = $request->input('user_id');
        $all        = $request->except('user_account','id','home_image','course_image','user_id');

        if($request->hasFile('home_image'))
        {
            $file = $request->file('home_image');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['home_image' => $fileName]);
        }

        if($request->hasFile('course_image'))
        {
            $file2 = $request->file('course_image');
            $fileName2 = $request->input('user_account'). '_' .$file2->getClientOriginalName();
            // $file2->move($tujuan_upload, $fileName2);
            $fileName2 = DB_global::cleanFileName($fileName2);
            Storage::putFileAs($this->folder_name, $file2, $fileName2, 'public');
            $all = array_merge($all, ['course_image' => $fileName2]);
        }


        $all = array_merge($all,
        array(
            'user_modified'=> $user_id,
            'date_modified' => DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_update($this->table_name,'id',$id,$all);

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
            $query = DB::table($this->table_name .' as a')->select('a.*',
                'x.title as menu_title',
                'b.title as section_title',
                'y.title as category_title',
                DB::RAW("concat(b.title,' - ' , x.title,' - ' , y.title) as category_menu"),
                // 'c.name as user_modified',
                'a.date_modified'
                )->leftJoin('awb_trn_category as y', 'a.category_id', '=', 'y.id')
                ->leftJoin('awb_mst_menu as x', 'y.menu_id', '=', 'x.id')
                ->leftJoin('awb_mst_section as b', 'x.section_id', '=', 'b.id')
                // ->leftJoin('users as c', 'a.user_modified', '=', 'c.id')
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

    public function ListCategory(Request $request)
	{
        $platform_id   = $request->input('platform_id');
        
        try {
            $query = DB::table('awb_trn_category as a')->select('a.id',
                DB::RAW("case when c.id = 6 then b.title
                when c.id = 7 then c.title
                else concat(c.title, ' - ',b.title,' - ' , a.title) end as title")
                )->leftJoin('awb_mst_menu as b', 'a.menu_id', '=', 'b.id')
                ->leftJoin('awb_mst_section as c', 'b.section_id', '=', 'c.id')
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', 1)
                ->whereIn('c.id', [8])
                ->orderBy('b.title')
                ->orderBy('a.title')
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
}
