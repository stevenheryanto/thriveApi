<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use AwbGlobal;
use App\Http\Controllers\Controller;
use App\Models\AWB\User;
use App\Models\AWB\awb_mst_user_profile;
use App\Models\AWB\awb_mst_config;
use App\Models\AWB\awb_platform_dtl_3;
use App\Models\AWB\awb_trn_email_subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $account = $request->input('account');
        try {
            $data = User::select('users.*',
            DB::RAW('CASE
                WHEN b.group_id = 2 THEN "super admin"
                ELSE ""
                END AS role'),
            )
            ->leftJoin('awb_platform_dtl_4 as b', 'b.user_id', '=', 'users.id')
            ->where('users.status_active', '=', 1)
            ->where('users.account', '=', $account)
            ->first();
            if(isset($data)){
                if (! $token = JWTAuth::fromUser($data)) {
                    return response()->json(['error' => 'invalid_credentials'], 400);
                }
            }else{
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('data', 'token'));
    }

    public function CheckUserAccess(Request $request)
	{
        $user_id = $request->input('user_id');
        $platform_id = $request->input('platform_id');
		$result = 0;
        try {
            if(awb_mst_user_profile::where('id', '=', $user_id)->where('platform_id', '=', $platform_id)->exists())
            {
                awb_mst_user_profile::where('id', '=', $user_id)
                    ->where('platform_id', '=', $platform_id)
                    ->update(['date_last_login' => DB_global::Global_CurrentDateTime()]);
                User::where('id', '=', $user_id)->update(['awb_last_access' => DB_global::Global_CurrentDateTime()]);
            }
            else
            {
                $result = 1;
                $user_id = trim($user_id);
                //---first time login
                $arrayProfile = array(
                    'id' => $user_id,
                    'level_id' => 1,
                    'points' => 0,
                    'streak_login_index' => 0,
                    'streak_login_level_start_date' => DB_global::Global_CurrentDatetime(),
                    'date_first_access' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id
                );
                DB_global::cz_insert('awb_mst_user_profile', $arrayProfile);
                User::where('id', '=', $user_id)->update(['awb_first_login' => DB_global::Global_CurrentDateTime()]);
                //---user get welcome point 50
                $configWelcomePoint = awb_mst_config::where('_code', '=', 'WELCOME_POINT')->value('value');
                $arrayHistory = array(
                    'user_id' => $user_id,
                    'point' => $configWelcomePoint,
                    'source' => 'welcome point (first login)',
                    'status_date' => DB_global::Global_CurrentDatetime(),
                    'user_modified' => $user_id,
                    'date_modified' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id
                );
                DB_global::cz_insert('awb_trn_point_history', $arrayHistory);
            }
            $data = AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);

            return response()->json([
                'data' => $data,
                'flag_first_login' => $result,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    //buat function untuk get platform
    public function getPlatform(Request $request){

        $user_id = $request->input('user_Id');
        $country = $request->input('country');
        $function = $request->input('function');

        $sql = "select DISTINCT a.id,a.name,a.platform_image,a.theme_id, (select case when b.group_id = 1 then 'admin' when b.group_id = 2 then 'super' else '' end as role from awb_platform_dtl_4 b where b.platform_id = a.id and b.user_id = ?) as role from awb_platform_hdr a left join awb_platform_dtl_1 c on c.id = a.id left join awb_platform_dtl_2 d on d.id = a.id where (c.country = ? or d.function =?)";
        $param=array(
            'user_id'=>$user_id,
            'country'=>$country,
            'function'=>$function
        );

        try {
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
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
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

    public function UpdateData(Request $request)
    {
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
            return response()->json([
                'data' => false,
                'message' => 'data update failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function AddData(Request $request)
    {
        $_arrayData = $request->input();
        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));

        try {
            $data = DB_global::cz_insert('users', $_arrayData);
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

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::bool_ValidateDataOnTableById_md5('users',$id);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListData(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');

        $platform_id = $request->input('platform_id');
        $chkCountry = DB_global::cz_result_set('SELECT country FROM awb_platform_dtl_1 WHERE platform_id=:platform_id', [$platform_id]);
        $chkDirectorate = DB_global::cz_result_set('SELECT directorate FROM awb_platform_dtl_2 WHERE platform_id=:platform_id', [$platform_id]);

        $arrCountry = [];
        if(count($chkCountry) > 0){
            foreach($chkCountry as $countryDtl){
                $arrCountry = array_merge($arrCountry, [$countryDtl->country]);
            }
        }

        $arrDirectorate = [];
        if(count($chkDirectorate) > 0){
            foreach($chkDirectorate as $directorateDtl){
                $arrDirectorate = array_merge($arrDirectorate, [$directorateDtl->directorate]);
            }
        }

        $adhoc_user = awb_platform_dtl_3::select('imdl_id')
            ->where([
                ['platform_id','=',$platform_id],
                ['flag_active','=',1]
            ]);

        try {
            $query = User::select('id','account','email','name','status_active','status_enable','awb_last_access as last_login')
            ->when(!is_null($where), function ($query) use ($where){
                $query->where(function($query) use ($where){
                    $query->Where('account','like', '%'.$where.'%')
                    ->orWhere('name','like', '%'.$where.'%')
                    ->orWhere('email','like', '%'.$where.'%');
                });
            })
            ->when(count($arrCountry) > 0, function ($query) use ($arrCountry) {
                $query->whereIn('country', $arrCountry);
            })
            ->when(count($arrDirectorate) > 0, function ($query) use ($arrDirectorate){
                $query->whereIn('directorate', $arrDirectorate);
            })
            ->orWhereIn('users.id', $adhoc_user);

            if($category != "COUNT"){
                $data = $query->when((isset($offset) and isset($limit)),
                        function ($query) use($offset, $limit){
                            $query->offset($offset)
                            ->limit($limit);
                        }
                    )
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

       try {
            $query = DB::table('users as a')->select('a.*',
            // 'b.group_id',
            'c.points as redeem_point',
            'c.tier_point',
            'd.title as user_level'
            )
            // ->leftJoin('awb_group_user as b', 'a.id', '=', 'b.user_id')
            ->leftJoin('awb_mst_user_profile as c', 'a.id', '=', 'c.id')
            ->leftJoin('awb_mst_level as d', 'c.level_id', '=', 'd.id')
            ->where(DB::RAW('md5(a.id)'), '=', $id)
            ;
            $data = $query->first();

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

    public function SelectDataByAccount(Request $request)
    {
        $account = $request->input('account');
        $platform_id = $request->input('platform_id');
        try {
            $user_id= User::select('users.id')
            ->where('users.account', '=', $account)
            ->first();

            $data = User::select('users.*',
            DB::RAW('CASE
                WHEN b.group_id = 2 THEN "super admin"
                ELSE ""
                END AS role'),
            )
            ->leftJoin('awb_platform_dtl_4 as b', 'b.user_id', '=', 'users.id')
            ->where('users.status_active', '=', 1)
            ->where('users.account', '=', $account)
            ->first();
            $data2 = AwbGlobal::UpdateUserPointAndLevel($user_id->id, $platform_id);
            $array = array_merge($data->toArray(), $data2);
            return response()->json([
                'data' => $array,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function AddAdminRole(Request $request)
    {
        $_arrayData = $request->input('arrayData');
        try {
                $data = DB_global::cz_insert('awb_platform_dtl_4',$_arrayData,false);

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

    public function CheckUserEmail(Request $request){
        $email = $request->input('email');
        $sql = "select * from users where status_active = 1 and email = ?";
        try {
            //code...
                $data = DB_global::cz_result_array($sql,[$email]);
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

    public function GetUserScore(Request $request)
    {
        $userID = $request->input('userId');
        $platform_id = $request->input('platform_id');
        $sql = "select ifnull(sum(vw.total_score),0) as grand_total
        from signature a left join
            (
                select a.signature,sum(b.point_score) as total_score
                from behavior a right join
                    user_vote b on a.id = b.behavior_id
                where
                    b.user_id = ?
                    and a.hashtag is not null group by a.signature
            ) as vw on a.id = vw.signature
            where a.platform_id = ? and a.status_active=1
            ";
        try {
            //code...
                $data = DB_global::cz_select($sql,[$userID,$platform_id],'grand_total');
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

    public function GetTotalUserPost(Request $request)
	{
        $userID = $request->input('userId');
        $platform_id = $request->input('platform_id');
        $sql = "SELECT ifnull(count(id),0) AS total_post FROM user_post where user_created = ? and status_active = 1 and platform_id = ?";
        try {
            //code...
                $data = DB_global::cz_select($sql,[$userID,$platform_id],'total_post');
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
            $data = User::where('account',$account)->update(['profile_picture'=>$photos]);
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

    public function SubscribeEmail(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $optionFlag  = $request->input('optionFlag');
        $platform_id  = $request->input('platform_id');
        $isMobileAccess = $request->input('isMobileAccess');

        if(awb_trn_email_subscription::where('id', '=', $user_id)->where('platform_id', '=', $platform_id)->doesntExist())
		{
			$arraySub = array(
				'id' => $user_id,
				'flag_subscription' => $optionFlag,
				'date_subscription' => DB_global::Global_CurrentDatetime(),
				'platform_id' => $platform_id
			);
			DB_global::cz_insert('awb_trn_email_subscription', $arraySub);

			// user get subscribe point
			$configSubscribePoint = awb_mst_config::where('_code', '=', 'SUBSCRIBE_POINT')->value('value');
			$arrayHistory = array(
				'user_id' => $user_id,
				'point' => $configSubscribePoint,
				'source' => 'Email subcribe bonus point',
				'status_date' => DB_global::Global_CurrentDatetime(),
				'user_modified' => $user_id,
				'date_modified' => DB_global::Global_CurrentDatetime(),
                'platform_id' => $platform_id
			);
			DB_global::cz_insert('awb_trn_point_history', $arrayHistory);
		}
		else
		{
            awb_trn_email_subscription::where('id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->update(['date_subscription' => DB_global::Global_CurrentDatetime(),
                    'flag_subscription' => $optionFlag
            ]);
		}
        $data = AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);
        AwbGlobal::GenerateLog($userData, $platform_id, 'Subscribe', 'Email subscription request', $isMobileAccess);
        try {
            return response()->json([
                'data' => $data['returnCase'],
                'message' => 'subscribe success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public 	function CheckReferralAccount(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id = $userData->id;
        $referByUserAccount = $request->input('referByUserAccount');
        $platform_id  = $request->input('platform_id');

        $sql = "select * from awb_platform_hdr where md5(id) = ? limit 1";
        $dataPlatform = DB_global::cz_result_array($sql,[$platform_id]);

        $platform_id = $dataPlatform['id'];

		$res = false;
		$referId = User::where('account', '=', $referByUserAccount)->value('id');
        if ($referId != '')
        {
			$arrayRef = array(
				'user_id' => $referId,
				'refer_user_id' => $user_id,
				'refer_date' => DB_global::Global_CurrentDatetime(),
                'platform_id' => $platform_id
			);
			DB_global::cz_insert('awb_trn_referral', $arrayRef);

			// user get welcome point 50
            $configReferralPoint = awb_mst_config::where('_code', '=', 'REFERRAL_POINT')->value('value');
			$arrayHistory = array(
				'user_id' => $referId,
				'point' => $configReferralPoint,
				'source' => 'referral bonus point',
				'status_date' => DB_global::Global_CurrentDatetime(),
				'user_modified' => $user_id,
				'date_modified' => DB_global::Global_CurrentDatetime(),
                'platform_id' => $platform_id
			);
			DB_global::cz_insert('awb_trn_point_history', $arrayHistory);
		   	$res = true;
        }
        try {
            return response()->json([
                'data' => $res,
                'data2' => $platform_id,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function Unsubscribe(Request $request)
	{
        $platform_id  = $request->input('platform_id');
        $reason  = $request->input('reason');
        $user_id  = $request->input('user_id');
		try {
            DB::table('awb_trn_email_subscription')
                ->where([
                    'id' => $user_id,
                    'platform_id' => $platform_id
                ])->update([
                    'date_subscription' => DB_global::Global_CurrentDatetime(),
                    'flag_subscription' => 0,
                    'unsubscribe_reason' => $reason,
                ]);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListPointHistory(Request $request)
    {
        /* ListTerms use flag_active */
        $category = $request->input('category');
        $userId = $request->input('md5UserID');

        try {
            $query = DB::table('awb_trn_point_history as a')->select(
                'a.*'
                )->join('users as b', 'b.id', '=', 'a.user_id')
                ->where(DB::RAW('md5(b.id)'), '=', $userId)
                ;

            $data = $query->orderBy('a.id','desc')->get();

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

    public function ListStreakLogin(Request $request)
    {
        /* ListTerms use flag_active */
        $category = $request->input('category');
        $userId = $request->input('md5UserID');

        try {
            $query = DB::table('awb_trn_login_level as a')->select(
                    'a.id','a.login_index', 'a.date_login',
					'b.title as current_level', 'c.title as target_level'
                    )->leftJoin('awb_mst_level as b', 'a.current_level', '=', 'b.id')
                    ->leftJoin('awb_mst_level as c', 'a.target_level', '=', 'c.id')
                    ->where(DB::RAW('md5(a.user_id)'), '=', $userId)
                    ;

            $data = $query->orderBy('a.current_level','desc')
                    ->orderBy('a.date_login','asc')
                    ->get();

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

    public function ListContentLevel(Request $request)
    {
        /* ListTerms use flag_active */
        $category = $request->input('category');
        $userId = $request->input('md5UserID');

        try {
            $query = DB::table('awb_trn_content_level as a')->select(
                    'a.*'
                    )->where(DB::RAW('md5(a.user_id)'), '=', $userId)
                    ;

            $data = $query->orderBy('a.id','desc')
                    ->get();

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

    public function AdjustPoint(Request $request)
    {
        $user_id = $request->input('user_id');
        $user_modified=$request->input('user_modified');
        $point_add=$request->input('point_add');
        $notes = $request->input('notes');
        $operator = $request->input('operator');
        $platform_id =  $request->input('platform_id');

        try {
            $_arrayData = array(
                'user_id'=>$user_id,
                'point'=>($operator == 'substract' ? -($point_add) : $point_add) ,
                'source'=> '(' . ($operator == 'substract' ? 'reduction' : 'additional') . ' point) ' . $notes,
                'status_date'=> DB_global::Global_CurrentDatetime(),
                'user_modified'=> $user_modified,
                'date_modified'=> DB_global::Global_CurrentDatetime(),
                'platform_id'=>$platform_id
            );

            $data = DB_global::cz_insert('awb_trn_point_history', $_arrayData);

            // check row exist in awb_mst_user_profile
            $query = DB::table('awb_mst_user_profile as a')
                    ->select('a.*')
                    ->where('a.id','=',$user_id);

            $countRow = $query->count();
            if($countRow == 0){
                $arrayData = array(
                    'id'=> trim($user_id),
                    'level_id'=> 1,
                    'points'=> 0,
                    'date_first_access'=> DB_global::Global_CurrentDatetime(),
                    'platform_id'=>$platform_id
                );
                $insert = DB_global::cz_insert('awb_mst_user_profile',$arrayData,false);
            }

            $update= AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);

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

    public function InsertUserContent(Request $request)
    {
        $user_id = $request->input('user_id');
        $user_modified=$request->input('user_modified');
        $user_created=$request->input('user_created');
        $content_title=$request->input('content_title');
        $content_date = $request->input('content_date');
        $contentDescription = $request->input('contentDescription');
        $platform_id =  $request->input('platform_id');

        try {
            $_arrayData = array(
                'user_id'=>$user_id,
                'content_title'=>$content_title ,
                'content_date'=>$content_date,
                'content_description'=>$contentDescription,
                'user_created'=>$user_created,
                'date_created'=> DB_global::Global_CurrentDatetime(),
                'user_modified'=> $user_modified,
                'date_modified'=> DB_global::Global_CurrentDatetime(),
                'platform_id'=>$platform_id
            );

            $data = DB_global::cz_insert('awb_trn_content_level', $_arrayData);

            // check row exist in awb_mst_user_profile
            $query = DB::table('awb_trn_content_level as a')
                    ->select('a.*')
                    ->where('a.user_id','=',$user_id);

            $countRow = $query->count();

            $update = DB::table('awb_mst_user_profile as a')
                    ->where('platform_id', '=', $platform_id)
                    ->where('id','=',$user_id)
                    ->update([
                        'submitted_content_total' => $countRow
                    ]);

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

}
