<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use AwbGlobal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\AWB\awb_mst_menu;
use App\Models\AWB\awb_mst_terms;
use App\Models\AWB\awb_mst_user_profile;
use App\Models\AWB\awb_trn_course_attended;
use App\Models\AWB\awb_trn_point_history;
use App\Models\AWB\awb_trn_article_log;
use App\Models\AWB\awb_trn_badge;
use App\Models\AWB\awb_trn_redeem_code_list;
use App\Models\AWB\awb_trn_redeem_code;
use App\Models\AWB\awb_trn_reward_claim;
use App\Models\AWB\awb_trn_quiz_user;
use App\Models\AWB\awb_user_pref_topic;
use App\Models\AWB\awb_trn_log;
use App\Models\AWB\awb_trn_article_import_detail;
use App\Models\AWB\awb_trn_content_level;
use App\Models\AWB\awb_trn_referral;
use App\Models\AWB\awb_trn_submit_idea;

use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function RsProfileTeam(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');

        $sql = "SELECT ifnull(a.points,0) as redeem_point,
                ifnull(a.tier_point,0) as tier_point,
                ifnull(x.total,0) as total_content_viewed,
                ifnull(y.total,0) as total_badges_archieved,
                ifnull(z.total,0) as total_course_attended,
                ifnull(b.name,b.full_name) as name, b.account, b.title, b.id, b.profile_picture
            from awb_mst_user_profile a
            right join
                users b on a.id = b.id
            left join
                (select count(id) as total, user_id, platform_id
                from awb_trn_article_log
                group by user_id) x on x.user_id = b.id
                and x.platform_id = a.platform_id
            left join
                (select count(id) as total, user_id, platform_id
                from awb_trn_badge_achieved
                group by user_id) as y on y.user_id = b.id
                and y.platform_id = a.platform_id
            left join
                (select count(id) as total, user_id, platform_id
                from awb_trn_course_attended
                group by user_id) z on z.user_id = b.id
                and z.platform_id = a.platform_id
            where b.status_active = 1
            and a.platform_id = :platform_id
            and (supervisor_id = :user_id
            or supervisor_id IN
                (select id FROM users WHERE supervisor_id = :user_id2)
            )
            order by b.name";
        $param = ([
            'user_id' => $user_id,
            'user_id2' => $user_id,
            'platform_id' => $platform_id
        ]);

        $sqlAdhoc="SELECT * FROM awb_platform_dtl_3 where imdl_id = ? and platform_id = ?";

        $dataUserAdHoc = DB_global::cz_result_set($sqlAdhoc, [$user_id, $platform_id]);

        try {
            // if(count($dataUserAdHoc)>0){
                $data = DB_global::cz_result_set($sql, $param);
            // // }else{
            //     $data = [];
            // }
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

    public function RsProfileCourseAttended(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $query = awb_trn_course_attended::where('user_id', '=', $user_id)
            ->where('platform_id', '=', $platform_id)
            ->orderBy('id', 'DESC');
        try {
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

    public function RsProfilePointHistory(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $query = awb_trn_point_history::select('source as title', 'point')
            ->where('user_id', '=', $user_id)
            ->where('platform_id', '=', $platform_id)
            ->where('point', '<>', 0)
            ->orderBy('id', 'DESC');
        try {
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

    public function RsProfileMostViewedTopic(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $logData = awb_trn_article_log::select(
                DB::raw('count(a.id) as total'),
                'd.title')
            ->leftJoin('awb_trn_article as b', 'b.id', '=', 'awb_trn_article_log.trn_article_id')
            ->leftJoin('awb_trn_category as c', 'c.id', '=', 'b.id')
            ->leftJoin('awb_mst_menu as d', 'd.id', '=', 'c.menu_id')
            ->where('user_id', '=', $user_id)
            ->where('platform_id', '=', $platform_id)
            ->groupBy('d.title')
            ;
        $query = DB::table(DB::raw("({$logData->toSql()})"))
            ->limit(5)
            ->orderBy('total');
	    $sql = "SELECT * FROM (
				select d.title, count(a.id) as total
				from awb_trn_article_log a
                left join
					awb_trn_article b on a.trn_article_id = b.id
                left join
					awb_trn_category c on b.category_id = c.id
                left join
					awb_mst_menu d on d.id = c.menu_id
				where a.user_id = :user_id
                and a.platform_id = :platform_id
				group by d.title
			) as xrz_data
			order by total desc
			limit 5";
        try {
            // $data = $query->get();
            $param = ([
                'user_id' => $user_id,
                'platform_id' => $platform_id
            ]);
            $data = DB_global::cz_result_set($sql, $param);
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

    public function RsProfileContentViewed(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
		$sql = "SELECT b.title, b.title_ind, DATE_FORMAT(a.date_read,'%W %D %M %Y') as date_read
            FROM awb_trn_article_log a
            LEFT JOIN awb_trn_article b ON a.trn_article_id = b.id
                AND a.platform_id = b.platform_id
            WHERE a.user_id = :user_id
            AND a.platform_id = :platform_id
            ORDER BY a.date_read DESC";
        try {
            $param = ([
                'user_id' => $user_id,
                'platform_id' => $platform_id
            ]);
            $data = DB_global::cz_result_set($sql, $param);
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

    public function RsProfileBadgesAchieved(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $showPublishOnly = $request->input('showPublishOnly');

        try {
            $data = $this->RsProfileBadgesAchievedInternal($user_id, $platform_id, $showPublishOnly);
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

    public function RsProfileReferral(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');

        $sql = "SELECT b.account, ifnull(b.name,b.full_name) as name, DATE_FORMAT(a.refer_date,'%W %D %M %Y %H:%i') as refer_date
            FROM awb_trn_referral a
            LEFT JOIN users b on a.refer_user_id = b.id
            WHERE a.user_id = :user_id
            AND a.platform_id = :platform_id
            ORDER BY a.refer_date DESC";
        try {
            $param = ([
                'user_id' => $user_id,
                'platform_id' => $platform_id
            ]);
            $data = DB_global::cz_result_set($sql, $param);
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

    public function InsertCourseAttended(Request $request)
    {
        $_arrayData = $request->input();
        try {
            if($request->hasFile('course_file'))
            {
                $file = $request->file('course_file');
                $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
                $fileName = DB_global::cleanFileName($fileName);
                Storage::putFileAs('learn/course', $file, $fileName, 'public');
                unset($_arrayData['course_file']);
                $_arrayData = array_merge($_arrayData, ['course_attachment' => $fileName]);
            }
            unset($_arrayData['user_account']);
            $_arrayData = array_merge($_arrayData,
            array(
                'date_created'=> DB_global::Global_CurrentDatetime(),
                'date_modified'=> DB_global::Global_CurrentDatetime()
            ));
            $data = DB_global::cz_insert('awb_trn_course_attended', $_arrayData);
            return response()->json([
                'data' => true,
                'message' => 'course insert success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'course insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function SelectCourseDetail(Request $request)
	{
        $_md5ID = $request->input('_md5ID');
        try {
            $data = awb_trn_course_attended::where(DB::raw('md5(id)'), '=', $_md5ID)->get();
            return response()->json([
                'data' => $data,
                'message' => 'data select success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data select failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function UpdateCourseAttended(Request $request)
	{
        $id = $request->input('id');
        $all = $request->except('user_account', 'id', 'course_file');
        if($request->hasFile('course_file'))
        {
            $file = $request->file('course_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs('learn/course', $file, $fileName, 'public');
            $all = array_merge($all, ['course_attachment' => $fileName]);
        }
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update('awb_trn_course_attended', 'id', $id, $all);
            return response()->json([
                'data' => true,
                'message' => 'course update success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'course update failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function DeleteCourseAttended(Request $request)
	{
        $id = $request->input('id');
        try {
            $data = DB_global::cz_delete('awb_trn_course_attended', 'id', $id);
            return response()->json([
                'data' => true,
                'message' => 'course delete success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'course delete failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function CheckClaimRedeemCode(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $code = $request->input('code');

        // validation (true, num_rows == 1) : check status active, code redeem
        $param = ([
            'code' => $code,
            'platform_id' => $platform_id
        ]);
        $query = "SELECT a.*
            FROM awb_trn_redeem_code_list a
            LEFT JOIN
                (select count(id) as total_redeem_claim, redeem_code_list_id
                from awb_trn_redeem_code
                where flag_active = 1
                group by redeem_code_list_id
                ) as b
                on a.id = b.redeem_code_list_id
                and b.platform_id = a.platform_id
            WHERE a.flag_active = 1
            AND a.code = :code
            AND a.platform_id = :platform_id
            AND (
                b.total_redeem_claim < a.qty_redeem_quota
                or
                (a.qty_redeem_quota > 0 and b.total_redeem_claim is null)
                )";
        $count = DB_global::cz_result_array($query, $param, false, 'COUNT');
        $data = DB_global::cz_result_array($query, $param);
        $res = '';
        if ($count > 0)
        {
            //validation : duplicate usage for the same code
            if(awb_trn_redeem_code::where('redeem_code_list_id', '=', $data['id'])
                ->where('user_id','=',$user_id)
                ->where('platform_id', '=', $platform_id)
                ->exists()
            ){
                $res = '';
            } else {
                awb_trn_redeem_code_list::where('id', '=', $data['id'])
                    ->where('platform_id', '=', $platform_id)
                    ->update(['last_date_redeem' => DB_global::Global_CurrentDatetime()]);

                $arrayRedeemCode = array(
                    'flag_redeem' => 1,
                    'user_id' => $user_id,
                    'redeem_code_list_id' => $data['id'],
                    'date_redeem' => DB_global::Global_CurrentDatetime(),
                    'code' => $code,
                    'points' => $data['points'],
                    'flag_active' => 1,
                    'user_created' => $user_id,
                    'date_created' => DB_global::Global_CurrentDatetime(),
                    'user_modified' => $user_id,
                    'date_modified' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id,
                );
                DB_global::cz_insert('awb_trn_redeem_code', $arrayRedeemCode);

                $arrayPointHistory = array(
                    'user_id' => $user_id,
                    'point' => $data['points'],
                    'source' => 'Claim redeem code ' . $data['code'],
                    'status_date' => DB_global::Global_CurrentDatetime(),
                    'user_modified' => $user_id,
                    'date_modified' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id
                );
                DB_global::cz_insert('awb_trn_point_history', $arrayPointHistory);
                $res = $data['points'];
                AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);
            }
        }
        try {
            return response()->json([
                'data' => $res,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function UpdateNotifierStatus(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        $user_id = $userData->id;
        $statusCode = $request->input('statusCode');

        try {
            if(substr($statusCode, 0, 1) == '0')
            {
                $statusCode = '1'.substr($statusCode, 1, 5);
                awb_mst_user_profile::where('id', '=', $user_id)
                    ->where('platform_id', '=', $platform_id)
                    ->update(['notifier_status' => $statusCode]);
            }
            return response()->json([
                'data' => true,
                'notifier_status' => $statusCode,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GetUserProfile(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $account = $request->input('account');

        $sql = "SELECT b.*,
                ifnull(a.name,a.full_name) as name,
                a.account,
                h.streak_login,
                b.streak_login_index,
                d.title as current_level_descr
            FROM users a
            RIGHT JOIN awb_mst_user_profile b on a.id = b.id
            AND b.platform_id = :platform_id
            LEFT JOIN awb_mst_level d on b.level_id = d.id
            LEFT JOIN awb_mst_level h on d.target_level_id = h.id
            WHERE a.account = :account ";
        try {
            $param = ([
                'account' => $account,
                'platform_id' => $platform_id
            ]);
            $data = DB_global::cz_result_array($sql, $param);
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

    function GenerateBadgeAchievement($rs, $mstBadgeId, $user_id, $platform_id)
	{
		foreach($rs as $drow)
		{
			if($drow->id == $mstBadgeId && $drow->flag_achieved == 0)
			{
                If(DB::table('awb_trn_badge_achieved')->where('user_id', '=', $user_id)
                    ->where('platform_id', '=', $platform_id)
                    ->where('badge_id', '=', $drow->id)
                    ->doesntExist()){
                    $array_data = array(
                        'badge_id' => $drow->id,
                        'user_id' => $user_id,
                        'achieved_date' => DB_global::Global_CurrentDatetime(),
                        'platform_id' => $platform_id
                    );
                    DB_global::cz_insert('awb_trn_badge_achieved', $array_data);
                }
				return true;
			}
			else if($drow->id == $mstBadgeId && $drow->flag_achieved == 1)
			{
				return false;
			}
		}
		return false;
	}

    function RsProfileBadgesAchievedInternal($user_id, $platform_id, $showPublishOnly)
	{
        try {
            $query = awb_trn_badge::select('awb_trn_badge.id',
                'awb_trn_badge.title',
                'awb_trn_badge.short_descr',
                'awb_trn_badge.short_descr_ind',
                'awb_trn_badge.badge_image',
                DB::RAW('CASE WHEN awb_trn_badge_achieved.id is not null then 1 else 0 end as flag_achieved')
            )->leftJoin('awb_trn_badge_achieved', function($join) use($user_id){
                $join->on('awb_trn_badge_achieved.badge_id', '=', 'awb_trn_badge.id')
                    ->where('awb_trn_badge_achieved.user_id', '=', $user_id);
            })
            ->where('awb_trn_badge.flag_active', '=', 1)
            ->where('awb_trn_badge.platform_id', '=', $platform_id)
            ->when(isset($showPublishOnly), function($query) use($showPublishOnly){
                $query->where('awb_trn_badge.flag_publish','=', $showPublishOnly);
            })
            ->orderBy('awb_trn_badge.id', 'asc');
            // echo $query->toSql();
            $data = $query->get();
            return $data;
        } catch (\Throwable $th) {
            return 'failed: '.$th;
        }
	}

    public function ServicesProfilesBadges(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        if($request->has('user_id')){
            $user_id = $request->input('user_id');
        }else{
            $user_id = $userData->id;
        }
        $currentLoginStreak = $request->input('currentLoginStreak');
        $currentLevelIdx = $request->input('currentLevelIdx');
        $flag_subscription = $request->input('flag_subscription');

        try{
            $totalAchievement_ClassHero = awb_trn_quiz_user::where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->where('answer_result', '=', 1)
                ->count();
            $rsCurrentArchievement = $this->RsProfileBadgesAchievedInternal($user_id, $platform_id, 1);
            if($totalAchievement_ClassHero >= 25)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,1,$user_id,$platform_id);
            }
            if($totalAchievement_ClassHero >= 50)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,2,$user_id,$platform_id);
            }
            if($totalAchievement_ClassHero >= 100)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,3,$user_id,$platform_id);
            }
            echo 'hero: '.$totalAchievement_ClassHero.PHP_EOL;

            if($currentLoginStreak >= 10 || $currentLevelIdx >= 2)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,4,$user_id,$platform_id);
            }
            if($currentLoginStreak >= 20  || $currentLevelIdx >= 4)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,5,$user_id,$platform_id);
            }
            if($currentLoginStreak >= 30)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,6,$user_id,$platform_id);
            }
            echo 'streak: '.$currentLoginStreak.PHP_EOL;

            $totalAchievement_TheMillionaire = awb_trn_reward_claim::where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->count();
            if($totalAchievement_TheMillionaire >= 1)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,7,$user_id,$platform_id);
            }
            if($totalAchievement_TheMillionaire >= 5)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,8,$user_id,$platform_id);
            }
            if($totalAchievement_TheMillionaire >= 10)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,9,$user_id,$platform_id);
            }
            echo 'mill: '.$totalAchievement_TheMillionaire.PHP_EOL;

            $totalAchievement_IdeaMaster = awb_trn_submit_idea::where('user_created', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->count();
            if($totalAchievement_IdeaMaster >= 10)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,10,$user_id,$platform_id);
            }
            if($totalAchievement_IdeaMaster >= 20)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,11,$user_id,$platform_id);
            }
            if($totalAchievement_IdeaMaster >= 30)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,12,$user_id,$platform_id);
            }
            echo 'idea: '.$totalAchievement_IdeaMaster.PHP_EOL;

            $totalAchievement_GreatCollaborator = awb_trn_content_level::where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->count();
            if($totalAchievement_GreatCollaborator >= 1)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,13,$user_id,$platform_id);
            }
            if($totalAchievement_GreatCollaborator >= 5)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,14,$user_id,$platform_id);
            }
            if($totalAchievement_GreatCollaborator >= 10)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,15,$user_id,$platform_id);
            }
            echo 'collab: '.$totalAchievement_GreatCollaborator.PHP_EOL;

            $totalAchievement_ThoughtfulFriend = awb_trn_referral::where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->count();
            if($totalAchievement_ThoughtfulFriend >= 1)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,16,$user_id,$platform_id);
            }
            if($totalAchievement_ThoughtfulFriend >= 5)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,17,$user_id,$platform_id);
            }
            if($totalAchievement_ThoughtfulFriend >= 10)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,18,$user_id,$platform_id);
            }
            echo 'fren: '.$totalAchievement_ThoughtfulFriend.PHP_EOL;

            $totalAchievement_FastPacer = awb_trn_log::where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->where('access_device', '=', 'Mobile')
                ->count();
            if($totalAchievement_FastPacer >= 1)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,19,$user_id,$platform_id);
            }
            if($totalAchievement_FastPacer >= 10)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,20,$user_id,$platform_id);
            }
            if($totalAchievement_FastPacer >= 20)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,21,$user_id,$platform_id);
            }
            echo 'fast: '.$totalAchievement_FastPacer.PHP_EOL;

            $totalAchievement_MasterCommunicator = awb_trn_article_import_detail::where('user_employee_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->where('article_action', '=', 'SHARE')
                ->distinct()
                ->count('article_id');
            if($totalAchievement_MasterCommunicator >= 10)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,22,$user_id,$platform_id);
            }
            if($totalAchievement_MasterCommunicator >= 20)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,23,$user_id,$platform_id);
            }
            if($totalAchievement_MasterCommunicator >= 30)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,24,$user_id,$platform_id);
            }
            echo 'comm: '.$totalAchievement_MasterCommunicator.PHP_EOL;

            if($flag_subscription == '1')
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,25,$user_id,$platform_id);
            }
            echo 'subs: '.$flag_subscription.PHP_EOL;

            $totalAchievement_Weekendlogin = awb_trn_log::where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->where(DB::raw("DAYNAME(log_date) IN ('Saturday','Sunday')"))
                ->count();
            if($totalAchievement_Weekendlogin > 0)
            {
                $this->GenerateBadgeAchievement($rsCurrentArchievement,26,$user_id,$platform_id);
            }
            echo 'wiken: '.$totalAchievement_Weekendlogin.PHP_EOL;
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

    public function readLeaderboard(Request $request)
	{
        /* readLeaderboard does not need user_id */
        /* readLeaderboardFromId needs user_id */
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        try {
            $subHistory = awb_trn_point_history::select('user_id',
                DB::raw('SUM(point) as historypoint'))
                ->whereRaw('year(status_date ) = year(curdate())
                AND month(status_date ) = month(curdate())
                AND point > 0
                AND platform_id = ?', [$platform_id]
                )->groupBy('user_id');
            $query = awb_trn_quiz_user::select('awb_trn_quiz_user.user_modified',
                'u.name',
                'u.account',
                'u.profile_picture',
                DB::RAW('sum(awb_trn_quiz_user.point) AS quizpoint'),
                'h.historypoint'
            )->leftJoin('users as u', 'u.id','=','awb_trn_quiz_user.user_modified')
            ->leftJoinSub($subHistory, 'h', function ($join){
                $join->on('h.user_id', '=', 'awb_trn_quiz_user.user_modified');
            })
            ->whereRaw('year(awb_trn_quiz_user.date_modified ) = year(curdate())
                AND month(awb_trn_quiz_user.date_modified ) = month(curdate())
                AND awb_trn_quiz_user.answer_result = 1
                AND awb_trn_quiz_user.platform_id = ?', [$platform_id])
            ->when(isset($user_id), function($query) use($user_id){
                $query->where('awb_trn_quiz_user.user_modified', '=', $user_id);
            })
            ->groupBy('user_modified')
            ->orderBy('historypoint', 'desc')
            ->orderBy('quizpoint', 'desc');

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

    public function listPreferredTopic(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            $query = awb_mst_menu::select('awb_mst_menu.*')
                ->leftJoin('awb_mst_section as b', 'b.id', '=', 'awb_mst_menu.section_id')
                ->where('awb_mst_menu.platform_id', '=', $platform_id)
                ->where('awb_mst_menu.flag_active', '=', 1)
                ->where('awb_mst_menu.section_id', '<>', 6)
                ->whereNotIn('awb_mst_menu.id', [34,35])
                ->orderBy('b.sort_index', 'asc')
                ->orderBy('awb_mst_menu.sort_index', 'asc');
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

    public function createPreferredTopic(Request $request)
	{
        $all = $request->input();
        try{
            DB_global::cz_insert('awb_user_pref_topic', $all);
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

	public function readPreferredTopic(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        try{
            $query = awb_user_pref_topic::where('userid', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->select('topicid');
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

	public function deletePreferredTopic(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        try{
            $query = awb_user_pref_topic::where('userid', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->delete();
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

    public function ListTerms(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try{
            $query = awb_mst_terms::select('awb_mst_terms.*',
                    'b.name as user_modified',
                    'b.date_modified'
                )
                ->leftJoin('users as b', 'b.id', '=', 'awb_mst_terms.user_modified')
                ->where('awb_mst_terms.flag_active', '=', 1)
                ->where('awb_mst_terms.platform_id', '=', $platform_id)
                ->orderBy('awb_mst_terms.sort_index');
            // echo $query->toSql();
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
}
