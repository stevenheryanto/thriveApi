<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_trn_badge;
use Tymon\JWTAuth\Facades\JWTAuth;

class BadgeController extends Controller
{
    
    protected $folder_name = 'learn/badge';
    protected $table_name = 'awb_trn_badge';


    public function ListData(Request $request)
    {

        $limit      = $request->input('limit');
        $offset     = $request->input('offset');
        $category   = $request->input('category');
        $export     = $request->input('export');
        $platform_id = $request->input('platform_id');

        try {

            $query = DB::table($this->table_name.' as a')->select('a.*')
                ->where('a.platform_id','=',$platform_id);

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('a.id','asc')
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
        $_arrayData     = $request->input();

        $platform_id    = $request->input('platform_id');
        $user_id        = $request->input('user_id');

        $_arrayData = $request->except('user_account','badge_image');
        
        $file       = $request->file('badge_image');
        $fileName   = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName   = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['badge_image'], $_arrayData['user_id']);

        $_arrayData = array_merge($_arrayData,
        array(
            'badge_image'        => $fileName,
            'date_created'  => DB_global::Global_CurrentDatetime(),
            'user_created'  => $user_id,
            'date_modified' => DB_global::Global_CurrentDatetime(),
            'user_modified' => $user_id,
            'platform_id'   => $platform_id
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
        $all        = $request->except('user_account','id','badge_image','user_id');

        if($request->hasFile('badge_image'))
        {
            $file = $request->file('badge_image');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
            $all = array_merge($all, ['badge_image' => $fileName]);
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
            $query =  awb_trn_badge::where(DB::RAW('md5(id)'), '=', $id);
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

    public function ListMember(Request $request)
    {	
        $category = $request->input('category');
        $offset = $request->input('offset');
        $limit = $request->input('limit');
        $badge_id = $request->input('badge_id');
        
        try {
            $query = DB::table('awb_trn_badge_achieved as a')
                ->selectRaw('b.account,b.email,b.name, a.id,a.badge_id')
                ->leftJoin('users as b', 'a.user_id', '=', 'b.id')
                ->where('a.badge_id', '=', $badge_id);

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    // ->orderBy('a.id', 'DESC')
                    ->get();
            } else {
                $data = $query->count();
            }
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function ValidateMemberExist($badge_id, $user_id)
    {	
    
        $query = DB::table('awb_trn_badge_achieved')
            ->where('badge_id', '=', $badge_id)
            ->where('user_id','=',$user_id);

        $data = $query->count();
        if($data>0){
            return true;
        }else{
            return false;
        }
            
    }

    public function MemberAdd(Request $request)
    {	
        $badge_id = $request->input('badge_id');
        $user_id = $request->input('user_id');
        $platform_id    = $request->input('platform_id');
        $token = $request->bearerToken();
        $userData = JWTAuth::toUser($token);
        
        try {
            $member_exist = $this->ValidateMemberExist($badge_id,$user_id);

            if (!$member_exist){
                $array_data = array(
					'badge_id'=> $badge_id,						
					'user_id'=> $user_id,
					'achieved_added_by'=>$userData->id,
					'achieved_date'=>DB_global::Global_CurrentDatetime(),
                    'platform_id'=>$platform_id
				);	
                $data = DB::table('awb_trn_badge_achieved')->insertGetId($array_data);
                return response()->json([
                    'data' => $data,
                    'message' => 'success'
                ]);
            }else{
                return response()->json([
                    'data' => 'exist',
                    'message' => 'user already exist'
                ]);
            }  
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function RemoveMember(Request $request)
    {
        $id = $request->input('id');
        
        try {
            $data = DB::table('awb_trn_badge_achieved')
            ->where('id','=',$id)
            ->delete();
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
