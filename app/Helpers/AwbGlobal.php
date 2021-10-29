<?php

// use DB_global;
use Illuminate\Support\Facades\DB;
use App\Models\AWB\awb_trn_point_history;
use App\Models\AWB\awb_trn_login_level;
use App\Models\AWB\awb_mst_user_profile;
use Tymon\JWTAuth\Facades\JWTAuth;

class AwbGlobal
{
	public static function AutoCheckUserProfile($user_id, $platform_id = 4, $loginDate = null)
	{
		if($loginDate == NULL){
			$loginDate = DB_global::Global_CurrentDate();
		}
		$sql = "UPDATE awb_mst_user_profile a
			LEFT JOIN
				(SELECT min(convert(date_login,date)) AS start_date, user_id , current_level, platform_id
				FROM awb_trn_login_level
				GROUP BY user_id, current_level
				) b
			ON a.level_id = b.current_level
			AND a.id = b.user_id
			AND a.platform_id = b.platform_id
			SET a.streak_login_level_start_date = IFNULL(b.start_date, :loginDate),
				a.streak_login_index =
					CASE
					WHEN (b.user_id IS NULL) THEN 0
						ELSE a.streak_login_index
					END
			WHERE a.id = :user_id
			AND a.platform_id = :platform_id";
		$param = (['user_id' => $user_id,
			'platform_id' => $platform_id,
			'loginDate' => $loginDate
		]);
		DB_global::cz_execute_query($sql, $param);
	}

	public static function GetUserProfileAndTarget($user_id, $platform_id = 4)
	{
		self::AutoCheckUserProfile($user_id, $platform_id, DB_global::Global_CurrentDate());
		$sql = "SELECT a.*,
			b.title as level_title,
			c.streak_login,
			b.generate_content,
			b.target_level_id,
			CASE
				WHEN IFNULL(a.tier_point, 0) >= c.points_needed
				THEN 1 ELSE 0
			END AS flag_target_point_reached,
			CASE
				WHEN IFNULL(a.submitted_content_total, 0) >= IFNULL(c.generate_content, 0)
				THEN 1 ELSE 0
			END AS flag_target_submitted_content_reached,
			IFNULL(d.flag_subscription,0) as flag_subscription
			FROM awb_mst_user_profile a
			LEFT JOIN awb_mst_level b ON a.level_id = b.id
			LEFT JOIN awb_mst_level c on b.target_level_id = c.id
			LEFT JOIN awb_trn_email_subscription d ON a.id = d.id
			WHERE a.id = :user_id
			AND a.platform_id = :platform_id
			LIMIT 1";
		$param = (['user_id' => $user_id,
			'platform_id' => $platform_id,
		]);
		return DB_global::cz_result_array($sql, $param);
	}

	public static function UserLoginStreak($user_id, $platform_id = 4, $loginDate = null)
	{
		$returnCase = null;
		$param = (['user_id' => $user_id,
			'platform_id' => $platform_id,
			'loginDate' => $loginDate
		]);
		$sql = "SELECT * FROM awb_trn_login_level WHERE user_id = :user_id
			AND convert(date_login, date) = :loginDate
			AND platform_id = :platform_id";
		$raCheck = DB_global::cz_result_array($sql, $param, false, 'COUNT');
		if($raCheck <= 0)
		{
			if(self::IsHoliday($loginDate))
			{
				$returnCase = 0;
				return $returnCase;
			}
			$ra = self::GetUserProfileAndTarget($user_id, $platform_id);
			$currentLevel = $ra['level_id'];

			if($currentLevel == "5")
			{
				//echo '<p>max level : ' . $currentLevel . '</p>';
				$returnCase = 0;
				return $returnCase;
			}
			$targetLevel = $ra['target_level_id'];
			$targetStreakDate = $ra['streak_login'];
			$currentStreakLoginIndex = DB_global::is_number($ra['streak_login_index']) ? $ra['streak_login_index'] : 0;
			$flagTargetPointReached = ($ra['flag_target_point_reached'] == '1' ? true : false);
			$flagTargetSubmittedContentReached = ($ra['flag_target_submitted_content_reached'] == '1' ? true : false);
			$flagEmailSubscription = ($ra['flag_subscription'] == '1' ? true : false);

			if($targetStreakDate == $currentStreakLoginIndex)
			{
			/*
				syarat untuk naik level :
					1. target point level tujuan terpenuhi
					2. khusus untuk tujuan level 4 & 5 harus memenuhi syarat submit content
					3. pada saat point akan naik, maka user pada saat tersebut harus subscribed email
			*/
				if($flagTargetPointReached && $flagTargetSubmittedContentReached && $flagEmailSubscription)
				{
					self::StreakLoginLevelUp($user_id, $platform_id, $targetLevel);
					$raUpdated = self::GetUserProfileAndTarget($user_id, $platform_id);
					$targetLevel = $raUpdated['target_level_id'];
					$currentLevel = $raUpdated['level_id'];

					$array_data = array(
						'login_index'=>1,
						'user_id'=>$user_id,
						'date_login'=>$loginDate,
						'current_level'=> $currentLevel,
						'target_level' => $targetLevel,
						'platform_id' => $platform_id
					);
					DB_global::cz_insert('awb_trn_login_level',$array_data,false);
					awb_mst_user_profile::where('id', '=', $user_id)
						->where('platform_id', '=', $platform_id)
						->update(['streak_login_index' => 1,
							'streak_login_level_start_date' => DB_global::Global_CurrentDate()
					]);
					$returnCase = 2;
				} else {
					$returnCase = 1;
				}
			} else {
				$boolValidateYesterdayLogin = ($currentStreakLoginIndex > 0 ? true : false); //jika sudah pernah ada log login sebelumnya maka perlu dicheck untuk tanggal h-1
				$resetStreakLogin = false;
				if($boolValidateYesterdayLogin)
				{
					//check tanggal kemarin
					$yesterdayDate = date("Y-m-d", strtotime("-1 days", strtotime($loginDate)));
					if(!self::IsHoliday($yesterdayDate))
					{
						if(awb_trn_login_level::where('user_id', '=', $user_id)
							->where('platform_id', '=', $platform_id)
							->where(DB::RAW('convert(date_login,date)'), '=', $yesterdayDate)
							->doesntExist())
						{
							//echo "<p>reset streak login yesterday date  : ".$yesterdayDate." </p> ";
							self::StreakLoginReset($user_id, $loginDate, $currentLevel, $platform_id);
							$resetStreakLogin = true;
							$currentStreakLoginIndex = 0;
						}
					}
				}
				//---generate login log
				$currentStreakLoginIndex +=1;
				$array_data = array(
					'login_index' => ($resetStreakLogin == true ? 1 : $currentStreakLoginIndex),
					'user_id' => $user_id,
					'date_login' => $loginDate,
					'current_level' => $currentLevel,
					'target_level' => $targetLevel,
					'platform_id' => $platform_id
				);
				DB_global::cz_insert('awb_trn_login_level', $array_data);
				awb_mst_user_profile::where('id', '=', $user_id)
					->where('platform_id', '=', $platform_id)
					->update(['streak_login_index' => $currentStreakLoginIndex
				]);
				if($currentStreakLoginIndex == $targetStreakDate && $flagTargetPointReached && $flagTargetSubmittedContentReached && $flagEmailSubscription)
				{
					//echo "<p><strong>level up !</strong></p>";
					self::StreakLoginLevelUp($user_id, $platform_id, $targetLevel);
					$returnCase = 2;
				} else {
					$returnCase = 1;
				}
			}
		} else {
			//echo '<p>existing date : ' . Global_ConvertDatetimeIndoFormat($loginDate). '</p>';
			$ra = self::GetUserProfileAndTarget($user_id, $platform_id);
			$currentLevel = $ra['level_id'];
			$targetLevel = $ra['target_level_id'];
			$targetStreakDate = $ra['streak_login'];
			$currentStreakLoginIndex = DB_global::is_number($ra['streak_login_index']) ? $ra['streak_login_index'] : 0;
			$flagTargetPointReached = ($ra['flag_target_point_reached'] == '1' ? true : false);
			$flagTargetSubmittedContentReached = ($ra['flag_target_submitted_content_reached'] == '1' ? true : false);
			$flagEmailSubscription = ($ra['flag_subscription'] == '1' ? true : false);

			if($targetStreakDate == $currentStreakLoginIndex)
			{
				/*
					syarat untuk naik level :
						1. target point level tujuan terpenuhi
						2. khusus untuk tujuan level 4 & 5 harus memenuhi syarat submit content
						3. pada saat point akan naik, maka user pada saat tersebut harus subscribed email
				*/
				if($flagTargetPointReached && $flagTargetSubmittedContentReached && $flagEmailSubscription)
				{
					self::StreakLoginLevelUp($user_id, $platform_id, $targetLevel);
					awb_mst_user_profile::where('id', '=', $user_id)
						->where('platform_id', '=', $platform_id)
						->update(['streak_login_index' => 0,
							'streak_login_level_start_date' => null
					]);
					$returnCase = 2;
				} else {
					$returnCase = 0;
				}
			} else {
				$returnCase = 0;
			}
		}
		return $returnCase;
	}

	public static function StreakLoginReset($user_id, $currentDate, $currentLevel, $platform_id)
	{
		awb_mst_user_profile::where('id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->update(['streak_login_index' => 1,
				'streak_login_level_start_date' => $currentDate
		]);
		awb_trn_login_level::where('user_id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->where('current_level', '=', $currentLevel)
			->delete();
	}

	public static function StreakLoginLevelUp($user_id, $platform_id = 4, $targetLevel)
	{
		/*
			xrzinfo
				script 1 :
					update table awb_mst_user_profile
						reset streak_login_level_start_date = null (*)
						reset streak_login_index = 0 (*)
						level_id = sesuai target level (*)
				script 2 :
					insert bonus point
		*/
		$update = awb_mst_user_profile::where('id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->update(['streak_login_level_start_date' => null,
				'streak_login_index' => 0,
				'level_id' => $targetLevel
			]);
		$param = ([
			'targetLevel' => $targetLevel,
			'platform_id' => $platform_id
		]);
		$raTarget = DB_global::cz_result_array("SELECT * FROM awb_mst_level WHERE id = :targetLevel AND platform_id = :platform_id ", $param);
		$array_data = ([
			'user_id' => $user_id,
			'point' => $raTarget['bonus_point'],
			'source'=> 'bonus ' . $raTarget['bonus_point'] . ' points to ' . $raTarget['title'],
			'status_date'=> DB_global::Global_CurrentDatetime(),
			'user_modified' => $user_id,
			'date_modified' => DB_global::Global_CurrentDatetime(),
			'platform_id' => $platform_id
		]);
		DB_global::cz_insert('awb_trn_point_history', $array_data);
	}

	static function IsHoliday($loginDate)
	{
		$boolIsHoliday = false;
		$raCheckHoliday = DB_global::cz_result_array("SELECT * FROM awb_mst_calendar
			WHERE convert(calendar_date, date) = convert(:loginDate, date)", [$loginDate]);
		if(count($raCheckHoliday) > 0)
		{
			return true;
		}
		else
		{
			$nameOfDay = date('D', strtotime($loginDate));
			if($nameOfDay == 'Sun' || $nameOfDay == 'Sat')
			{
				return true;
			}
		}
		return false;
	}

    public static function UpdateUserPointAndLevel($user_id = null, $platform_id = 4)
	{
		/*
			return case :
				null : tdk masuk dalam case mana pun
				0 : validation error, karna data adalah holiday atau sudah di input sebelumnya
				1 : data berhasil di generate
				2 : kondisi no 1 dan user naik level
		*/
		$returnCase = null;
		$currentTierPoint = awb_trn_point_history::where('user_id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->where('point', '>', 0)
			->sum('point');

		$currentRedeemPoint = awb_trn_point_history::where('user_id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->sum('point');

		$update = awb_mst_user_profile::where('id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->update([
				'tier_point' => $currentTierPoint,
				'points' =>	$currentRedeemPoint
			]);

		$returnCase = self::UserLoginStreak($user_id, $platform_id, DB_Global::Global_CurrentDate());

		$data = awb_mst_user_profile::select('awb_mst_user_profile.level_id',
				'awb_mst_user_profile.streak_login_index',
				'd.title',
				DB::RAW('ifnull(d.title, "new kids on the block") as user_level'),
				DB::RAW('ifnull(d.id,1) as user_level_idx'),
				DB::RAW('ifnull(awb_mst_user_profile.points,0) as redeem_point'),
				DB::RAW('ifnull(awb_mst_user_profile.tier_point,0) as tier_point'),
				'f.flag_subscription',
				'h.streak_login',
				'd.generate_content',
				'd.target_level_id',
				'h.points_needed',
				DB::RAW('ifnull(awb_mst_user_profile.notifier_status,"000000") as notifier_status')
			)
			->leftJoin('awb_mst_level as d', 'd.id', '=', 'awb_mst_user_profile.level_id')
            ->leftJoin('awb_mst_level as h', 'h.id', '=', 'd.target_level_id')
            ->leftJoin('awb_trn_email_subscription as f', 'f.id', '=', 'awb_mst_user_profile.id')
			->where('awb_mst_user_profile.id', '=', $user_id)
			->where('awb_mst_user_profile.platform_id', '=', $platform_id)
			->first();
		// print_r($data);
		if($data->streak_login == 0 || $data->streak_login == null){
			$progressStreakLogin = 0;
		} else {
			$progressStreakLogin = round($data->streak_login_index / $data->streak_login * 100);
		}
		if($data->points_needed == 0 || $data->points_needed == null){
			$progressLevel = 0;
		} else{
			$progressLevel = round($currentTierPoint / $data->points_needed * 100);
		}

		return (['Cz_awb_point'=> $currentRedeemPoint,
			'Cz_awb_tier_point' => $currentTierPoint,
			'Cz_awb_level' => $data->title,
			'Cz_awb_level_idx' => $data->level_id,
			'Cz_awb_streak_login_target' => $data->streak_login,
			'Cz_awb_streak_login_current' => $data->streak_login_index,
			'user_level' => $data->user_level,
			'redeem_point' => $data->redeem_point,
			'tier_point' => $data->tier_point,
			'Cz_awb_email_subscribe' => $data->flag_subscription,
			'generate_content' => $data->generate_content,
			'target_level_id' => $data->target_level_id,
			'notifier_status' => $data->notifier_status,
			'targetPoint' => $data->points_needed,
			'progressStreakLogin' => $progressStreakLogin,
			'progressLevel' => $progressLevel,
			'returnCase' => $returnCase
		]);
	}

    public static function getUserData($token){
        try {
            //code...
            $userData=[];
            if($token!=''){
                $userData = JWTAuth::toUser($token);
            }
            return $userData;
        } catch (\Throwable $th) {
            //throw $th;
            return $th;
        }

    }

	static function UpdateUserPointAndLevelExport($user_id, $platform_id = 4)
	{
		$currentTierPoint = awb_trn_point_history::where('user_id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->where('point', '>', 0)
			->sum('point');

		$currentRedeemPoint = awb_trn_point_history::where('user_id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->sum('point');

		$update = awb_mst_user_profile::where('id', '=', $user_id)
			->where('platform_id', '=', $platform_id)
			->update([
				'tier_point' => $currentTierPoint,
				'points' =>	$currentRedeemPoint
			]);

		$returnCase = self::UserLoginStreak($user_id, $platform_id, DB_Global::Global_CurrentDate());
	}

	static function GenerateLog($userData, $platform_id, $logType, $logInfo, $isMobileAccess, $contentId = null)
	{
		$array_data = array(
            'user_name'=> $userData->name,
            'user_id'=> $userData->id,
            'user_account'=> $userData->account,
            'user_email'=> $userData->email,
            'log_type'=> $logType,
            'log_info'=> $logInfo,
            'log_date' => DB_global::Global_CurrentDatetime(),
            'access_device' => ($isMobileAccess ? 'Mobile' : 'Desktop'),
            'transaction_id' => $contentId,
            'platform_id' => $platform_id,
		);
        DB_global::cz_insert('awb_trn_log', $array_data);
	}

	public static function getActivePeriodInternal($platform_id)
	{
        try {
			$sql = "SELECT a.* FROM awb_mst_reg_period a
				WHERE reg_from <= CURDATE()
				AND reg_to >= CURDATE()
				AND platform_id = ?
				ORDER BY reg_from DESC limit 1";
        	$data = DB_global::cz_result_array($sql, [$platform_id]);
            return $data;
        } catch (\Throwable $th) {
            return $th;
        }
    }
}
