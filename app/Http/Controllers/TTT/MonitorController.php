<?php

namespace App\Http\Controllers\TTT;

use DB_global;
use App\Http\Controllers\Controller;
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

class MonitorController extends Controller{
    
    public function ViewData(Request $request){
        $Security_UserFunction = $request->input('user_func');
        $Security_UserId = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $returnData = array(
            'SampoernaEmployeeAverage'=> $this->AverageAllEmployee($platform_id),
			'YourRatingAverage'=> $this->AverageYourRating($Security_UserId, $platform_id),
			'YourFunctionAverage'=> $this->AverageYourFunction($Security_UserFunction, $platform_id)
            );
        return response()->json([
            'data' => $returnData,
            'message' => 'success'
        ]);
    }
    private function AverageAllEmployee($platform_id){
        $sql = "select substring(score_rating,2,1) as productivity, 
					case 
					when substring(score_rating,1,1) = 'A' then 1 
					when substring(score_rating,1,1) = 'B' then 2 
					when substring(score_rating,1,1) = 'C' then 3 
					when substring(score_rating,1,1) = 'D' then 4 end as positivity
                from timetothink_rating 
                where status_active = 1 
                and platform_id = $platform_id 
                and flag_draft = 0 and score_rating is not null";
		$rs = DB_global::cz_result_set($sql,[]);
		$productivity = 0;
		$positivity = 0;
		$total = 0;
		foreach($rs as $drow)
		{
			$total+=1;
			$productivity+=$drow->productivity;
			$positivity+=$drow->positivity;
		}


		//echo 'result productivity:'. round($productivity/$total,0,PHP_ROUND_HALF_UP);
		$result_productivity =($productivity > 0) ? round($productivity/$total,0,PHP_ROUND_HALF_UP) : '';
		//echo 'result positivity:'. round($positivity/$total,0,PHP_ROUND_HALF_UP);
		$result_positivity = ($positivity > 0) ? round($positivity/$total,0,PHP_ROUND_HALF_UP) : 0;
		switch ($result_positivity) {
			case '1':
				$result_positivity = 'A';
				break;
			case '2':
				$result_positivity = 'B';
				break;
			case '3':
				$result_positivity = 'C';
				break;
			case '4':
				$result_positivity = 'D';
				break;
			default:
				$result_positivity = '?';
				break;
		}

		return $result_positivity .$result_productivity;
    }

    private function AverageYourFunction($Security_UserFunction, $platform_id)
	{
		$sql = "select substring(a.score_rating,2,1) as productivity, 
				case 
				when substring(a.score_rating,1,1) = 'A' then 1 
				when substring(a.score_rating,1,1) = 'B' then 2 
				when substring(a.score_rating,1,1) = 'C' then 3 
				when substring(a.score_rating,1,1) = 'D' then 4 end as positivity
			from timetothink_rating a left join
				users c on a.user_organizer = c.id 
                where  a.score_rating is not null
                and a.platform_id = $platform_id
				and c.directorate = '".$Security_UserFunction . "';";
		$rs = DB_global::cz_result_set($sql,[]);
		$productivity = 0;
		$positivity = 0;
		$total = 0;
		foreach($rs as $drow)
		{
			$total+=1;
			$productivity+=$drow->productivity;
			$positivity+=$drow->positivity;
		}
		

		//echo 'result productivity:'. round($productivity/$total,0,PHP_ROUND_HALF_UP);
		$result_productivity =($productivity > 0) ? round($productivity/$total,0,PHP_ROUND_HALF_UP) : '';
		//echo 'result positivity:'. round($positivity/$total,0,PHP_ROUND_HALF_UP);
		$result_positivity = ($positivity > 0) ? round($positivity/$total,0,PHP_ROUND_HALF_UP) : 0;
		
		switch ($result_positivity) {
			case '1':
				$result_positivity = 'A';
				break;
			case '2':
				$result_positivity = 'B';
				break;
			case '3':
				$result_positivity = 'C';
				break;
			case '4':
				$result_positivity = 'D';
				break;
			default:
				$result_positivity = '?';
				break;
		}

		return $result_positivity .$result_productivity;
	}

    private function AverageYourRating($Security_UserId, $platform_id)
	{
		$sql = "select substring(a.score_rating,2,1) as productivity, 
				case 
				when substring(a.score_rating,1,1) = 'A' then 1 
				when substring(a.score_rating,1,1) = 'B' then 2 
				when substring(a.score_rating,1,1) = 'C' then 3 
				when substring(a.score_rating,1,1) = 'D' then 4 end as positivity
			from timetothink_rating_participant a left join
				timetothink_rating b on a.rating_id = b.id and a.platform_id = b.platform_id
                where b.status_active = 1 and b.flag_draft = 0 and a.platform_id = $platform_id 
                and a.user_participant = '". $Security_UserId . "';";
		$rs = DB_global::cz_result_set($sql,[]);
		$productivity = 0;
		$positivity = 0;
		$total = 0;
		foreach($rs as $drow)
		{
			$total+=1;
			$productivity+=$drow->productivity;
			$positivity+=$drow->positivity;
		}


		//echo 'result productivity:'. round($productivity/$total,0,PHP_ROUND_HALF_UP);
		$result_productivity =($productivity > 0) ? round($productivity/$total,0,PHP_ROUND_HALF_UP) : '';
		//echo 'result positivity:'. round($positivity/$total,0,PHP_ROUND_HALF_UP);
		$result_positivity = ($positivity > 0) ? round($positivity/$total,0,PHP_ROUND_HALF_UP) : 0;
		switch ($result_positivity) {
			case '1':
				$result_positivity = 'A';
				break;
			case '2':
				$result_positivity = 'B';
				break;
			case '3':
				$result_positivity = 'C';
				break;
			case '4':
				$result_positivity = 'D';
				break;
			default:
				$result_positivity = '?';
				break;
		}

		return $result_positivity .$result_productivity;
	}
}
