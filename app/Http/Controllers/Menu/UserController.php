<?php

namespace App\Http\Controllers\Menu;

use DB_global;
use App\Http\Controllers\Controller;
use App\Models\Menu\User;
use App\Models\Menu\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\JWTAuth as JWTAuthJWTAuth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->input('account');
        $config_maintenance = Config::whereRaw('parameter ="MAINTENANCE_MODE"')->first();

        if($config_maintenance->value=='TRUE'){
            $config_list = Config::whereRaw('parameter ="MAINTENANCE_MODE_USER_ACCOUNT"')->first();
            $list_maintenance = explode("|", $config_list->value);
			$where_maintenance = '';
			foreach($list_maintenance as $list) {
			    $where_maintenance = str_replace('~', ',', $where_maintenance) . "'" . $list . "'~";
			}
			$where_maintenance = str_replace('~', '', $where_maintenance);

			if($where_maintenance != '')
			{
				$where = " and account in ($where_maintenance) ";

            }

            $sql = "select a.*,case when b.group_id = 2 then 'super admin' else '' end as role,
                        case when first_login is null then 1 when DATEDIFF(now(), last_login) > 30 then 1 else 0 end as flag_show_web_tour
                    from users a left join
                        menu_platform_dtl_4 b on a.id = b.user_id
                    where status_active = 1
                    $where
                    limit 1";
            $param=[];
            $user = User::whereRaw("status_active = 1 $where")->first();
        }else{
            $sql = "select a.*,case when b.group_id = 2 then 'super admin' else '' end as role, case when first_login is null then 1 when DATEDIFF(now(), last_login) > 30 then 1 else 0 end as flag_show_web_tour from users a left join menu_platform_dtl_4 b on a.id = b.user_id where status_active = 1 and (a.email = :a or a.account = :b) limit 1";

            $user = User::whereRaw('status_active = 1 and (email = :email or account = :account)', array('email'=>$credentials,'account'=>$credentials))->first();

            $param = array(
                'a'=>$credentials,
                'b'=>$credentials
            );
        }

        $data = DB_global::cz_result_array($sql,$param);

        $date = date('Y-m-d H:i:s');

        $affectedRows = User::whereRaw('email = ? or account = ?', [$credentials,$credentials])->update([
            'last_login' => $date,
            'first_login' => ($user->first_login == null ? $date : $user->first_login),
        ]);
        //echo "<pre>";print_r($test);exit();
        try {
            if (! $token = JWTAuth::fromUser($user)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('data', 'token'));
    }

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $platform_id = $request->input('platform_id');
        $chkCountry = DB_global::cz_result_set('SELECT country FROM menu_platform_dtl_1 WHERE platform_id=:platform_id', [$platform_id]);
        $chkDirectorate = DB_global::cz_result_set('SELECT directorate FROM menu_platform_dtl_2 WHERE platform_id=:platform_id', [$platform_id]);

        $arrCountry = [];
        if(count($chkCountry) > 0){
            foreach($chkCountry as $countryDtl){
                $arrCountry = array_merge($arrCountry, [$countryDtl->country]);
            }
        }
        // print_r($arrCountry);

        $arrDirectorate = [];
        if(count($chkDirectorate) > 0){
            foreach($chkDirectorate as $directorateDtl){
                $arrDirectorate = array_merge($arrDirectorate, [$directorateDtl->directorate]);
            }
        }

        try {
            $query = User::select('id','account','email','name','status_active','status_enable','last_login')
            ->when(!is_null($where), function ($query) use ($where){
                $query->orWhere('account','like', '%'.$where.'%')
                ->orWhere('name','like', '%'.$where.'%')
                ->orWhere('email','like', '%'.$where.'%');
            })
            ->when(count($arrCountry) > 0, function ($query) use ($arrCountry) {
                $query->whereIn('country', $arrCountry);
            })
            ->when(count($arrDirectorate) > 0, function ($query) use ($arrDirectorate){
                $query->whereIn('directorate', $arrDirectorate);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('account')
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

    public function SelectData(Request $request)
    {
       $id = $request->input('md5ID');
       $sql = "select a.* from users a where md5(a.id) = ? limit 1";
       try {
        //code...
            $data = DB_global::cz_result_array($sql,[$id]);

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

    public function getPlatform(Request $request){

        $user_id = $request->input('user_Id');
        $country = $request->input('country');
        $function = $request->input('function');

        $sql = "select DISTINCT a.id,a.name,a.platform_image,a.theme_id, (select case when b.group_id = 1 then 'admin' when b.group_id = 2 then 'super' else '' end as role from menu_platform_dtl_4 b where b.platform_id = a.id and b.user_id = ?) as role from menu_platform_hdr a left join menu_platform_dtl_1 c on c.id = a.id left join menu_platform_dtl_2 d on d.id = a.id where (c.country = ? or d.function =?)";

        $param=array(
            'user_id'=>$user_id,
            'country'=>$country,
            'function'=>$function
        );

        try {
            //code...

                $data = DB_global::cz_result_set($sql,$param);

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

    public function logout_now(Request $request)
    {
        $token = $request->header( 'Authorization' );

        try {
            JWTAuth::parseToken()->invalidate( $token );

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, the user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function refreshToken(Request $request)
    {
        $token = $request->header( 'Authorization' );
        $credentials = $request->input('account');
        try {
            $user = User::whereRaw('status_active = 1 and (email = :email or account = :account)', array('email'=>$credentials,'account'=>$credentials))->first();
            $newToken = JWTAuth::fromUser($user);

        } catch (JWTException $e) {
            return response()->json([
                'error' => 'could_not_create_token',
            ], 500);
        }

        return response()->json(compact('newToken'));
    }

    public function getAuthenticatedUser()
    {
        try {

            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (TokenExpiredException $e) {

            return response()->json(['token_expired'], $e);

        } catch (TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e);

        } catch (JWTException $e) {

            return response()->json(['token_absent'], $e);

        }

        return response()->json(compact('user'));
    }

    public function UpdateData(Request $request){
        $id = $request->input('id');
        $all = $request->except('id');
        try {
            //code...

            $data = DB_global::cz_update('users','id',$id,$all);
            //$users = User::find($id);
            //echo "<pre>";print_r($users);exit();
            //$users->fill($all);
            //$users->save();

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

    public function AddData(Request $request){
        $_arrayData = $request->input();
        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));

        try {
            //code...
            $data = DB_global::cz_insert('users', $_arrayData);
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

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            //code...
            $data = DB_global::bool_ValidateDataOnTableById_md5('users',$id);
            return response()->json([
                'data' => true,
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

    public function ActivityLog(Request $request)
	{
        $userName = $request->input("userName");
        $userId = $request->input("userId");
        $userAccount = $request->input("userAccount");
        $userEmail = $request->input("userEmail");
        $isMobile = $request->input("isMobile");
        $moduleName = $request->input("moduleName");
        $feature = $request->input("feature");

        try {
            $dbGlobal = new DB_global;

            $dbGlobal->GenerateLog($userName,$userId,$userAccount,$userEmail,$isMobile,$moduleName,$feature);

            return response()->json([
                'data' => true,
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

    public function UpdatePhotos(Request $request)
    {
        ini_set('memory_limit','512M');
        $account  = $request->input("account");
        $photos = $request->input("photos");

        try {
            //code...
            $data = User::where('account',$account)->update(['profile_picture'=>$photos]);
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
}

