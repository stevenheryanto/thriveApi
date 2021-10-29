<?php

namespace App\Http\Controllers\TTT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use DB_global;

class MeetingController extends Controller
{
    //
    public function ListData(Request $request)
    {
        $currentDateTime = DB_global::Global_CurrentDatetime();

        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $user_account= $request->input('userId');
        $platform_id = $request->input('platform_id');

        $search = $request->input('search');
        $filter_period_from = $request->input('validity_period_from');
        $filter_period_to = $request->input('validity_period_to');

        $str_where = "";

        if(isset($search))
        {
            if(!empty($search))
            {
                 $str_where =  $str_where. " and (a.subject like :search1 or a.comment like :search2
                    or b.name like :search3 or a.score_rating like :search4) ";
                //Do my PHP code
            }
        }

        if(isset($filter_period_from) && isset($filter_period_to))
        {
            if(!empty($filter_period_from) && !empty($filter_period_to))
            {
                $str_where =  $str_where." and (convert(a.date_created,date) between :filter_period_from and :filter_period_to) ";
            }
        }


       $sql = "SELECT
                    distinct
                    a.id,
                    a.subject,
                    a.user_organizer,
                    a.comment,
                    a.score_rating as summary_score_rating,
                    a.status_enable,
                    a.status_active,
                    a.user_created,
                    a.date_created,
                    a.user_modified,
                    a.date_modified,
                    a.is_deleted,
                    b.name user_organizer_name,
                    case when HOUR(TIMEDIFF(a.date_created, convert('" .$currentDateTime. "',datetime))) < 24 then 0 else 1 end as flag_lock_rating,
                    c.score_rating,
                    case when c.score_rating is null then 1 else 0 end as flag_enable_rating,
                    c.flag_organizer,
                    a._type_rating
                FROM
                    timetothink_rating a
                        LEFT JOIN
                    users b ON a.user_organizer = b.id left join
                    timetothink_rating_participant c on a.id = c.rating_id and c.user_participant = :userParticipant
                WHERE
                    a.status_active = 1 and a.is_deleted = 0 and
                    a.flag_draft = 0 and a.platform_id = :platform_id and
                    a.id in (select rating_id from timetothink_rating_participant where user_participant = :userParticipant2)
                    $str_where
                ORDER BY a.date_created DESC";


        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT  :offset,:limit ";
            //code...
            $param = array(
            'limit'=>$limit,
            'offset'=>$offset,
            'platform_id'=>$platform_id,
            'userParticipant' => $user_account,
            'userParticipant2' =>$user_account
            );

            if(isset($search))
            {
                if(!empty($search))
                {
                    $param = array_merge($param,[
                        'search1'=> '%'.$search.'%',
                        'search2'=> '%'.$search.'%',
                        'search3'=> '%'.$search.'%',
                        'search4'=> '%'.$search.'%',
                    ]);
                    //Do my PHP code
                }
            }

            if(isset($filter_period_from) && isset($filter_period_to))
            {
                if(!empty($filter_period_from) && !empty($filter_period_to))
                {
                    // $filter_period_from = DB_global::Global_ConvDateIndToEng($filter_period_from);
                    // $filter_period_to = DB_global::Global_ConvDateIndToEng($filter_period_to);

                    $param = array_merge($param,[
                        'filter_period_from'=> $filter_period_from,
                        'filter_period_to'=> $filter_period_to,
                    ]);
                }
            }
        }else{
            //code...
            $param = array(
                'platform_id'=>$platform_id,
                'userParticipant' => $user_account,
                'userParticipant2' =>$user_account
            );

            if(isset($search))
            {
                if(!empty($search))
                {
                    $param = array_merge($param,[
                        'search1'=> '%'.$search.'%',
                        'search2'=> '%'.$search.'%',
                        'search3'=> '%'.$search.'%',
                        'search4'=> '%'.$search.'%',
                    ]);
                    //Do my PHP code
                }
            }

            if(isset($filter_period_from) && isset($filter_period_to))
            {
                if(!empty($filter_period_from) && !empty($filter_period_to))
                {
                    // $filter_period_from = DB_global::Global_ConvDateIndToEng($filter_period_from);
                    // $filter_period_to = DB_global::Global_ConvDateIndToEng($filter_period_to);

                    $param = array_merge($param,[
                        'filter_period_from'=> $filter_period_from,
                        'filter_period_to'=> $filter_period_to,
                    ]);
                }
            }
        }

        try {

            $data = DB_global::cz_result_set($sql,$param,false,$category);

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

   public function DeleteData(Request $request){
       $id = $request->input('id');
       try {
            $sql = "update timetothink_rating set is_deleted = 1,date_modified = now() where id = :id ";
            $anotherParam = array(
                'id'=>$id
            );
           //code...
           DB_global::cz_execute_query($sql,$anotherParam);
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
}
