<?php

namespace App\Http\Controllers\TTT;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB_global;
use App\Models\TTT\Timetothink_platform_dtl_1;
use App\Models\TTT\Timetothink_platform_dtl_2;
use App\Models\TTT\Timetothink_platform_dtl_3;
use App\Models\TTT\timetothink_platform_dtl_4;
use App\Models\TTT\User;

class InitiateController extends Controller
{
    //
    public function GetUserParticipant(Request $request){
        $platform_id = $request->input('platform_id');
        $selectedAccount = $request->input('selectedAccount');
        $account = $request->input('account_self');

        try {
            //code...
            $country = Timetothink_platform_dtl_1::select('country')->where('platform_id','=',$platform_id);

            $directorate = Timetothink_platform_dtl_2::select('directorate')->where('platform_id','=',$platform_id);

            $adhoc_user = Timetothink_platform_dtl_3::select('imdl_id')
            ->where([
                ['platform_id','=',$platform_id],
                ['flag_active','=',1],
                ['account','<>',$account]
            ]);

            $data = User::select('users.id','account','name')->where([
                ['users.status_active','=',1]
            ])
            ->whereNotNull('directorate')
            ->where(function($query) use ($selectedAccount){
                $arrSelectedAccount = json_decode($selectedAccount);
                if(count($arrSelectedAccount)>0){
                    $query->whereNotIn('users.id',$arrSelectedAccount);
                }
            })
            ->where(function($query) use ($country,$directorate,$adhoc_user){
                $query->whereIn('country',$country)->orWhereIn('directorate',$directorate)->orWhereIn('id',$adhoc_user);
            })
            ->orderBy('users.id', 'asc')->get();

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

    public function AddMember(Request $request){
        $id = $request->input('id');
        $score = $request->input('score');
        $itemParticipant = $request->input('initiate_participant');
        $arrParticipant = json_decode($itemParticipant);
        $userId = $request->input('user_account');
        $subject = $request->input('initiate_subject');
        $comment = $request->input('initiate_message');
        $platform_id = $request->input('platform_id');
        try {
            //code...
            if(DB_global::is_number($id)){
                $array_data = array(
                    'subject'=> trim($subject),
                    'comment'=> trim($comment),
                    'platform_id'=>$platform_id,
                    'user_modified'=>$userId,
                    'date_modified'=>DB_global::Global_CurrentDatetime()
                );

                DB_global::cz_update('timetothink_rating', 'id',$id, $array_data);
            }
            else{
                $array_data = array(
                    'platform_id'=>$platform_id,
                    'flag_draft'=> 1,
                    'subject'=> trim($subject),
                    'user_organizer'=> $userId,
                    'comment'=> trim($comment),
                    //'score_rating'=>(($typeRating =='Gabungan') ? $score : null),
                    'user_created'=>$userId,
                    'date_created'=>DB_global::Global_CurrentDatetime(),
                    'user_modified'=>$userId,
                    'date_modified'=>DB_global::Global_CurrentDatetime()
                );
                $id  = DB_global::cz_insert('timetothink_rating',$array_data,TRUE);
            }

            //insert organizer
            $array_detail = array(
                'platform_id'=>$platform_id,
                'rating_id'=> $id,
                'user_participant'=> $arrParticipant->value,
                'flag_organizer'=>($userId == $arrParticipant->value ? 1 :0 ),
                //'comment'=> trim($this->input->post('initiate_message')),
                'score_rating'=>$score,
                'user_created'=>$userId,
                'date_created'=>DB_global::Global_CurrentDatetime(),
                'user_modified'=>$userId,
                'date_modified'=>DB_global::Global_CurrentDatetime()
            );

            $sql = "SELECT * FROM timetothink_rating_participant where user_participant = :idparticipant and rating_id = :rating_id and platform_id = :platform_id";
            $anotherParam =  array(
                'idparticipant'=>$arrParticipant->value,
                'rating_id'=>$id,
                'platform_id'=>$platform_id
            );
            $ra = DB_global::cz_result_array($sql,$anotherParam);
            if (count($ra) > 0)
            {
                DB_global::cz_update('timetothink_rating_participant', 'id',$ra['id'], $array_detail);
            }
            else
            {
                DB_global::cz_insert('timetothink_rating_participant',$array_detail,TRUE);
            }

            $result = $this->UpdateRatingScore($id, $platform_id);

            $listParticipant = $this->GetListParticipant(md5($result),$platform_id);

            $dataMeeting = $this->GetDataMeeting(md5($result));

            return response()->json([
                'dataParticipant' => $listParticipant,
                'dataMeeting' => $dataMeeting,
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

    public function UpdateRatingScore($ratingId, $platform_id){

        $sql = "select substring(score_rating,2,1) as productivity,
					case
					when substring(score_rating,1,1) = 'A' then 1
					when substring(score_rating,1,1) = 'B' then 2
					when substring(score_rating,1,1) = 'C' then 3
					when substring(score_rating,1,1) = 'D' then 4 end as positivity
                from timetothink_rating_participant where rating_id = :rating_id and score_rating is not null and platform_id = :platform_id";
        $param = array(
            'rating_id'=>$ratingId,
            'platform_id'=>$platform_id
        );

        $rs = DB_global::cz_result_set($sql,$param);
        $productivity = 0;
		$positivity = 0;
        $total = 0;

        foreach($rs as $drow)
		{
			$total+=1;
			$productivity+=$drow->productivity;
			$positivity+=$drow->positivity;
        }

        $result_productivity = round($productivity/$total,0,PHP_ROUND_HALF_UP);
		$result_positivity = round($positivity/$total,0,PHP_ROUND_HALF_UP);
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

        $sqlUpdate = "update timetothink_rating set score_rating = :score_rating where id = :rating_id";
        $anotherParam = array(
            'score_rating'=>$result_positivity.$result_productivity,
            'rating_id'=>$ratingId,
        );

        try {
            //code...
            DB_global::cz_execute_query($sqlUpdate,$anotherParam);
            return $ratingId;
        } catch (\Throwable $th) {
            //throw $th;
            return $th;
        }
    }

    public function GetListParticipant($id,$platform_id){
        $sql = "SELECT a.id,b.name,a.score_rating,
                    a.flag_organizer, a.user_participant
                FROM timetothink_rating_participant a left join
                    users b on a.user_participant = b.id
                where  md5(rating_id) = :rating_id and platform_id = :platform_id
                order by a.flag_organizer desc, b.name";
        $param = array(
            'rating_id'=>$id,
            'platform_id'=>$platform_id
        );
        return DB_global::cz_result_set($sql,$param);
    }

    public function GetDataMeeting($id){
        $currentDateTime = DB_global::Global_CurrentDatetime();
        $sql = "select a.id, a.subject,
                    a.user_organizer, a.comment,
                    a.score_rating as score_rating_summary, a.status_enable, a.status_active, a.user_created,
                    b.name user_organizer_name,
                    c.score_rating,
                    case when HOUR(TIMEDIFF(a.date_created, convert('" . $currentDateTime . "',datetime))) < 24 then 0 else 1 end as flag_lock_rating,
                    d.name as participant_name, d.email as participant_email,
                    a._type_rating
                from timetothink_rating a left join
                    users b on a.user_organizer = b.id left join
                timetothink_rating_participant c on a.id = c.rating_id left join
                users d on c.user_participant = d.id
                where a.is_deleted = 0 and c.is_deleted = 0 and md5(a.id) = :id
                limit 1";
        $param = array(
            'id'=>$id
        );
        return DB_global::cz_result_array($sql,$param);
    }

    public function FormSubmit(Request $request){
        $id = $request->input('id');
        $userId = $request->input('user_account');
        $subject = $request->input('initiate_subject');
        $comment = $request->input('initiate_message');
        $platform_id = $request->input('platform_id');

        try {
            //code...
            if(DB_global::is_number($id)){
                $array_data = array(
                    'flag_draft'=> 0,
                    'subject'=> trim($subject),
                    'comment'=> trim($comment),
                    'platform_id'=>$platform_id,
                    'user_modified'=>$userId,
                    'date_modified'=>DB_global::Global_CurrentDatetime()
                );

                DB_global::cz_update('timetothink_rating', 'id',$id, $array_data);
            }
            else{
                $array_data = array(
                    'platform_id'=>$platform_id,
                    'flag_draft'=> 1,
                    'subject'=> trim($subject),
                    'user_organizer'=> $userId,
                    'comment'=> trim($comment),
                    //'score_rating'=>(($typeRating =='Gabungan') ? $score : null),
                    'user_created'=>$userId,
                    'date_created'=>DB_global::Global_CurrentDatetime(),
                    'user_modified'=>$userId,
                    'date_modified'=>DB_global::Global_CurrentDatetime()
                );
                $id  = DB_global::cz_insert('timetothink_rating',$array_data,TRUE);
            }

            $listParticipant = $this->GetListParticipant(md5($id),$platform_id);

            $dataMeeting = $this->GetDataMeeting(md5($id));

            return response()->json([
                'dataParticipant' => $listParticipant,
                'dataMeeting' => $dataMeeting,
                'dataId'=>md5($id),
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

    public function getDetail(Request $request){
        $id = $request->input('md5ID');
        $platform_id = $request->input('platform_id');
        try {
            //code...
            $listParticipant = $this->GetListParticipant($id,$platform_id);
            $dataMeeting = $this->GetDataMeeting($id);

            return response()->json([
                'dataParticipant' => $listParticipant,
                'dataMeeting' => $dataMeeting,
                'dataId'=>md5($id),
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
