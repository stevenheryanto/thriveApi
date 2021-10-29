<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use App\Models\TTR\recognize_platform_dtl_1;
use App\Models\TTR\recognize_platform_dtl_2;
use App\Models\TTR\recognize_platform_dtl_3;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Storage;
use App\Models\TTR\User;
use Illuminate\Support\Facades\Log;
use Aws\S3\Exception\S3Exception;
use App\Jobs\SendEmailRecognition;
class DashboardController extends Controller
{
    //
    public function RsListBehaviorSignature(Request $request)
    {
        $platform_id = $request->input('platform_id');

        $sql = "
				select b.signtag as signature , a.hashtag as behavior
					from behavior a left join signature b on a.signature = b.id
				where a.is_deleted = 0 and b.is_deleted = 0 and a.status_active = 1  and b.status_active = 1 and a.platform_id = ? and b.platform_id = ?
                order by a.signature";

        try {
            //code...
                $param = array(
                    $platform_id,
                    $platform_id
                );
                #print $sql;
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

    public function RsSignatureList(Request $request){
        $platform_id = $request->input('platform_id');

        $sql = "
                select id,signtag,signtag_ind
                    from signature
                where is_deleted = 0 and platform_id = ?";

        try {
            //code...
                $param = array(
                    $platform_id
                );
                #print $sql;
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

    public function RsEmailCardList(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $sql = "select * from recognize_email_card where status_active = 1 and platform_id = ? order by card_name asc";
        try {
            //code...
                $param = array(
                    $platform_id
                );
                #print $sql;
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

    public function RsListNewsfeed_PinnedAndAlgoritmByHierarchy(Request $request){
        $page = $request->input('page');
        $limit = $request->input('limit');
        $userId = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');

        $offset = ceil($page * $limit);
        if($category != "COUNT"){
            $sql = "SELECT *
            FROM (
                SELECT a.id
                    ,a.post_content
                    ,a.upload_image
                    ,a.point_score
                    ,a.total_user
                    ,a.total_behavior
                    ,b.name AS name
                    ,a.date_created
                    ,a.user_created
                    ,x.total_comment
                    ,y.total_like
                    ,IFNULL(comment_header.flag_like, 0) AS flag_like_header
                    ,CASE
                        WHEN a.user_created = :userId
                            THEN 1
                        ELSE 0
                        END AS flag_own_post
                    ,b.account
                    ,b.profile_picture
                    ,IFNULL(a.pinned_flag, 0) AS pinned_flag
                    ,x.name AS on_behalf_by_name
                    ,x.id AS on_behalf_by_account
                    ,a.flag_on_behalf_of
                    ,a.flag_email_card
                FROM user_post a
                LEFT JOIN users b ON a.user_created = b.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_comment
                    FROM user_comment
                    WHERE status_active = 1
                    GROUP BY user_post_id
                    ) AS x ON x.user_post_id = a.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_like
                    FROM user_like
                    WHERE flag_like = 1
                    GROUP BY user_post_id
                    ) AS y ON y.user_post_id = a.id
                LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                    AND comment_header.user_created = :userIds
                LEFT JOIN users x ON x.id = a.user_onbehalf_by
                WHERE a.status_active = 1
                    AND a.platform_id = :platform_id
                ) AS X
            ORDER BY pinned_flag DESC
                ,id DESC LIMIT :offset
                ,:limit";

            $param = array(
                'userIds'=>$userId,
                'userId'=>$userId,
                'offset'=>$offset,
                'limit'=>$limit,
                'platform_id'=>$platform_id
            );
        }else{
            $sql = "SELECT *
            FROM (
                SELECT a.id
                    ,a.post_content
                    ,a.upload_image
                    ,a.point_score
                    ,a.total_user
                    ,a.total_behavior
                    ,b.name AS name
                    ,a.date_created
                    ,a.user_created
                    ,x.total_comment
                    ,y.total_like
                    ,IFNULL(comment_header.flag_like, 0) AS flag_like_header
                    ,CASE
                        WHEN a.user_created = :userId
                            THEN 1
                        ELSE 0
                        END AS flag_own_post
                    ,b.account
                    ,b.profile_picture
                    ,IFNULL(a.pinned_flag, 0) AS pinned_flag
                    ,x.name AS on_behalf_by_name
                    ,x.id AS on_behalf_by_account
                    ,a.flag_on_behalf_of
                    ,a.flag_email_card
                FROM user_post a
                LEFT JOIN users b ON a.user_created = b.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_comment
                    FROM user_comment
                    WHERE status_active = 1
                    GROUP BY user_post_id
                    ) AS x ON x.user_post_id = a.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_like
                    FROM user_like
                    WHERE flag_like = 1
                    GROUP BY user_post_id
                    ) AS y ON y.user_post_id = a.id
                LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                    AND comment_header.user_created = :userIds
                LEFT JOIN users x ON x.id = a.user_onbehalf_by
                WHERE a.status_active = 1
                    AND a.platform_id = :platform_id
                ) AS X
            ORDER BY pinned_flag DESC
                ,id DESC ";

            $param = array(
                'userIds'=>$userId,
                'userId'=>$userId,
                'platform_id'=>$platform_id
            );
        }


        try {
            //code...

                if($category != "COUNT"){
                    $data = DB_global::cz_result_set($sql,$param,false,$category);
                    $hasil = [];
                    if(count($data)>0){
                        foreach ($data as $dataPost) {

                            $commentPerPost = $this->RsListUserComment($userId,md5($dataPost->id));
                            $dataPost->newsfeedComment = $commentPerPost;
                            $hasil[] = $dataPost;
                        }
                    }

                }else{
                    $hasil = DB_global::cz_result_set($sql,$param,false,$category);

                }

                return response()->json([
                    'data' => $hasil,
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

    public function HitsEmailNotification(Request $request){

        try {
            //code...

            $emailHits = $request->input('email');

            if($emailHits !=""){
                $sqlUpdate = "update email_log set view_count = ifnull(view_count,0) + 1 where md5(transaction_id) = :emailHits ";
                $paramUpdate = [
                    'emailHits'=> $emailHits
                ];
                DB_global::cz_execute_query($sqlUpdate,$paramUpdate);

                $getPlatformIdByPost = DB_global::cz_result_array("select platform_id from user_post where md5(id)=:emailHits",$paramUpdate);

                $getListSuperVisor = DB_global::cz_result_set("SELECT DISTINCT a.supervisor_id FROM `users` a left join user_vote b on b.user_id = a.id WHERE md5(b.user_post_id) = :emailHits",$paramUpdate);

                $dataPlatform = DB_global::cz_result_array('select * from recognize_platform_hdr where id = ?', [$getPlatformIdByPost['platform_id']]);

            }

                return response()->json([
                    'data' => $dataPlatform,
                    'dataSPV' => $getListSuperVisor,
                    'postID' => $emailHits,
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

    public function RsListNewsfeedAsReceiver(Request $request){
        $page = $request->input('page');
        $limit = $request->input('limit');
        $userId = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');
        $filter = $request->input('filter');
        $postId = $request->input('postId');
        // $emailHits = $request->input('email');

        // if($emailHits !=""){
        //     $sqlUpdate = "update email_log set view_count = ifnull(view_count,0) + 1 where md5(transaction_id) = :emailHits ";
        //     $paramUpdate = [
        //         'emailHits'=> $emailHits
        //     ];
        //     DB_global::cz_execute_query($sqlUpdate,$paramUpdate);
        // }

        $filterQuery = '';
        if($filter !=""){
            $filterQuery = " and (b.account like :filter)";
        }

        $offset = ceil($page * $limit);
        if($category != "COUNT"){

            $param = array(
                'userId1'=>$userId,
                'userId2'=>$userId,
                'userId3'=>$userId,
                'offset'=>$offset,
                'limit'=>$limit,
                'platform_id'=>$platform_id
            );

            if(isset($postId)){
                unset($param['userId2']);
                $param = array_merge($param,['postId'=>$postId]);
                $sql="SELECT a.id
                    ,a.post_content
                    ,a.upload_image
                    ,a.point_score
                    ,a.total_user
                    ,a.total_behavior
                    ,b.name AS name
                    ,a.date_created
                    ,a.user_created
                    ,x.total_comment
                    ,y.total_like
                    ,IFNULL(comment_header.flag_like, 0) AS flag_like_header
                    ,CASE
                        WHEN a.user_created = :userId1
                            THEN 1
                        ELSE 0
                        END AS flag_own_post
                    ,b.account
                    ,b.profile_picture
                    ,0 AS pinned_flag
                    ,x.name AS on_behalf_by_name
                    ,x.id AS on_behalf_by_account
                    ,a.flag_on_behalf_of
                    ,a.flag_email_card
                FROM user_post a
                LEFT JOIN users b ON a.user_created = b.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_comment
                    FROM user_comment
                    WHERE status_active = 1
                    GROUP BY user_post_id
                    ) AS x ON x.user_post_id = a.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_like
                    FROM user_like
                    WHERE flag_like = 1
                    GROUP BY user_post_id
                    ) AS y ON y.user_post_id = a.id
                LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                    AND comment_header.user_created = :userId3
                LEFT JOIN users x ON x.id = a.user_onbehalf_by
                WHERE a.status_active = 1 AND a.platform_id = :platform_id AND md5(a.id) = :postId $filterQuery
                ORDER BY a.id DESC LIMIT :offset,:limit
                ";
            }else{
                    $sql = "SELECT a.id
                    ,a.post_content
                    ,a.upload_image
                    ,a.point_score
                    ,a.total_user
                    ,a.total_behavior
                    ,b.name AS name
                    ,a.date_created
                    ,a.user_created
                    ,x.total_comment
                    ,y.total_like
                    ,IFNULL(comment_header.flag_like, 0) AS flag_like_header
                    ,CASE
                        WHEN a.user_created = :userId1
                            THEN 1
                        ELSE 0
                        END AS flag_own_post
                    ,b.account
                    ,b.profile_picture
                    ,0 AS pinned_flag
                    ,x.name AS on_behalf_by_name
                    ,x.id AS on_behalf_by_account
                    ,a.flag_on_behalf_of
                    ,a.flag_email_card
                FROM (
                    select distinct user_post_id as id
                    from user_vote
                        where user_id = :userId2
                    ) AS xxx
                LEFT JOIN user_post a ON xxx.id = a.id
                LEFT JOIN users b ON a.user_created = b.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_comment
                    FROM user_comment
                    WHERE status_active = 1
                    GROUP BY user_post_id
                    ) AS x ON x.user_post_id = a.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_like
                    FROM user_like
                    WHERE flag_like = 1
                    GROUP BY user_post_id
                    ) AS y ON y.user_post_id = a.id
                LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                    AND comment_header.user_created = :userId3
                LEFT JOIN users x ON x.id = a.user_onbehalf_by
                WHERE a.status_active = 1 AND a.platform_id = :platform_id $filterQuery
                ORDER BY a.id DESC LIMIT :offset,:limit";
            }
            if($filter !=""){
                $param = array_merge($param,['filter' => '%'.$filter.'%',]);
            }
        }else{

            $param = array(
                'userId1'=>$userId,
                'userId2'=>$userId,
                'userId3'=>$userId,
                'platform_id'=>$platform_id
            );

            if(isset($postId)){
                unset($param['userId2'],$param['userId1']);
                $param = array_merge($param,['postId'=>$postId]);
                $sql ="SELECT a.id
                    FROM user_post a
                    LEFT JOIN users b ON a.user_created = b.id
                    LEFT JOIN (
                        SELECT user_post_id
                            ,COUNT(id) AS total_comment
                        FROM user_comment
                        WHERE status_active = 1
                        GROUP BY user_post_id
                        ) AS x ON x.user_post_id = a.id
                    LEFT JOIN (
                        SELECT user_post_id
                            ,COUNT(id) AS total_like
                        FROM user_like
                        WHERE flag_like = 1
                        GROUP BY user_post_id
                        ) AS y ON y.user_post_id = a.id
                    LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                        AND comment_header.user_created = :userId3
                    LEFT JOIN users x ON x.id = a.user_onbehalf_by
                    WHERE a.status_active = 1 AND a.platform_id = :platform_id AND md5(a.id) = :postId $filterQuery
                    ORDER BY a.id DESC ";
            }else{
                    $sql = "SELECT a.id
                    ,a.post_content
                    ,a.upload_image
                    ,a.point_score
                    ,a.total_user
                    ,a.total_behavior
                    ,b.name AS name
                    ,a.date_created
                    ,a.user_created
                    ,x.total_comment
                    ,y.total_like
                    ,IFNULL(comment_header.flag_like, 0) AS flag_like_header
                    ,CASE
                        WHEN a.user_created = :userId1
                            THEN 1
                        ELSE 0
                        END AS flag_own_post
                    ,b.account
                    ,b.profile_picture
                    ,0 AS pinned_flag
                    ,x.name AS on_behalf_by_name
                    ,x.id AS on_behalf_by_account
                    ,a.flag_on_behalf_of
                    ,a.flag_email_card
                FROM (
                    select distinct user_post_id as id
                    from user_vote
                        where user_id = :userId2
                    ) AS xxx
                LEFT JOIN user_post a ON xxx.id = a.id
                LEFT JOIN users b ON a.user_created = b.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_comment
                    FROM user_comment
                    WHERE status_active = 1
                    GROUP BY user_post_id
                    ) AS x ON x.user_post_id = a.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,COUNT(id) AS total_like
                    FROM user_like
                    WHERE flag_like = 1
                    GROUP BY user_post_id
                    ) AS y ON y.user_post_id = a.id
                LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                    AND comment_header.user_created = :userId3
                LEFT JOIN users x ON x.id = a.user_onbehalf_by
                WHERE a.status_active = 1 AND a.platform_id = :platform_id $filterQuery
                ORDER BY a.id DESC";
            }

            if($filter !=""){
                $param = array_merge($param,['filter' => '%'.$filter.'%',]);
            }
        }

        try {
            //code...

                if($category != "COUNT"){
                    $data = DB_global::cz_result_set($sql,$param,false,$category);
                    $hasil = [];
                    if(count($data)>0){
                        foreach ($data as $dataPost) {
                            $commentPerPost = $this->RsListUserComment($userId,md5($dataPost->id));
                            $dataPost->newsfeedComment = $commentPerPost;
                            $hasil[] = $dataPost;
                        }
                    }

                }else{
                    $hasil = DB_global::cz_result_set($sql,$param,false,$category);
                }

                return response()->json([
                    'data' => $hasil,
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

    public function RsListNewsfeedAsContributor(Request $request){
        $page = $request->input('page');
        $limit = $request->input('limit');
        $userId = $request->input('user_id');
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');
        $filter = $request->input('filter');

        $filterQuery = '';
        if($filter !=""){
            $filterQuery = " and (a.post_content like :filter)";
        }

        $offset = ceil($page * $limit);
        if($category != "COUNT"){
            $sql = "SELECT a.id
                        ,a.post_content
                        ,a.upload_image
                        ,a.point_score
                        ,a.total_user
                        ,a.total_behavior
                        ,b.name AS name
                        ,a.date_created
                        ,a.user_created
                        ,x.total_comment
                        ,y.total_like
                        ,IFNULL(comment_header.flag_like, 0) AS flag_like_header
                        ,CASE
                            WHEN a.user_created = :userId1
                                THEN 1
                            ELSE 0
                            END AS flag_own_post
                        ,b.account
                        ,b.profile_picture
                        ,0 AS pinned_flag
                        ,x.name AS on_behalf_by_name
                        ,x.id AS on_behalf_by_account
                        ,a.flag_on_behalf_of
                        ,a.flag_email_card
                    FROM (
                        select id
						from user_post
							where user_created = :userId2
                        ) AS xxx
                    LEFT JOIN user_post a ON xxx.id = a.id
                    LEFT JOIN users b ON a.user_created = b.id
                    LEFT JOIN (
                        SELECT user_post_id
                            ,COUNT(id) AS total_comment
                        FROM user_comment
                        WHERE status_active = 1
                        GROUP BY user_post_id
                        ) AS x ON x.user_post_id = a.id
                    LEFT JOIN (
                        SELECT user_post_id
                            ,COUNT(id) AS total_like
                        FROM user_like
                        WHERE flag_like = 1
                        GROUP BY user_post_id
                        ) AS y ON y.user_post_id = a.id
                    LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                        AND comment_header.user_created = :userId3
                    LEFT JOIN users x ON x.id = a.user_onbehalf_by
                    WHERE a.status_active = 1 AND a.platform_id = :platform_id $filterQuery
                    ORDER BY a.id DESC LIMIT :offset,:limit";

            $param = array(
                'userId1'=>$userId,
                'userId2'=>$userId,
                'userId3'=>$userId,
                'offset'=>$offset,
                'limit'=>$limit,
                'platform_id'=>$platform_id
            );

            if($filter !=""){
                $param = array_merge($param,['filter' => '%'.$filter.'%',]);
            }
        }else{
            $sql = "SELECT a.id
                        ,a.post_content
                        ,a.upload_image
                        ,a.point_score
                        ,a.total_user
                        ,a.total_behavior
                        ,b.name AS name
                        ,a.date_created
                        ,a.user_created
                        ,x.total_comment
                        ,y.total_like
                        ,IFNULL(comment_header.flag_like, 0) AS flag_like_header
                        ,CASE
                            WHEN a.user_created = :userId1
                                THEN 1
                            ELSE 0
                            END AS flag_own_post
                        ,b.account
                        ,b.profile_picture
                        ,0 AS pinned_flag
                        ,x.name AS on_behalf_by_name
                        ,x.id AS on_behalf_by_account
                        ,a.flag_on_behalf_of
                        ,a.flag_email_card
                    FROM (
                        select id
						from user_post
							where user_created = :userId2
                        ) AS xxx
                    LEFT JOIN user_post a ON xxx.id = a.id
                    LEFT JOIN users b ON a.user_created = b.id
                    LEFT JOIN (
                        SELECT user_post_id
                            ,COUNT(id) AS total_comment
                        FROM user_comment
                        WHERE status_active = 1
                        GROUP BY user_post_id
                        ) AS x ON x.user_post_id = a.id
                    LEFT JOIN (
                        SELECT user_post_id
                            ,COUNT(id) AS total_like
                        FROM user_like
                        WHERE flag_like = 1
                        GROUP BY user_post_id
                        ) AS y ON y.user_post_id = a.id
                    LEFT JOIN user_like AS comment_header ON comment_header.user_post_id = a.id
                        AND comment_header.user_created = :userId3
                    LEFT JOIN users x ON x.id = a.user_onbehalf_by
                    WHERE a.status_active = 1 AND a.platform_id = :platform_id $filterQuery
                    ORDER BY a.id DESC";

            $param = array(
                'userId1'=>$userId,
                'userId2'=>$userId,
                'userId3'=>$userId,
                'platform_id'=>$platform_id
            );

            if($filter !=""){
                $param = array_merge($param,['filter' => '%'.$filter.'%',]);
            }
        }

        try {
            //code...

                if($category != "COUNT"){
                    $data = DB_global::cz_result_set($sql,$param,false,$category);
                    $hasil = [];
                    if(count($data)>0){
                        foreach ($data as $dataPost) {
                            $commentPerPost = $this->RsListUserComment($userId,md5($dataPost->id));
                            $dataPost->newsfeedComment = $commentPerPost;
                            $hasil[] = $dataPost;
                        }
                    }

                }else{
                    $hasil = DB_global::cz_result_set($sql,$param,false,$category);
                }

                return response()->json([
                    'data' => $hasil,
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

    public function RsStatisticScore(Request $request)
	{
		//'[{"name":"#teamwork"},{"name":"#leadership"},{"name":"#problem-solving"},{"name":"#innovation"},{"name":"#customer-service"},{"name":"#vision"},{"name":"#new"}]';

        $userId = $request->input('user_id');
        $platform_id = $request->input('platform_id');

        $sqlGrandTotal = "select ifnull(sum(vw.total_score),0) as grand_total
        from signature a left join
            (
                select a.signature,sum(b.point_score) as total_score
                from behavior a right join
                    user_vote b on a.id = b.behavior_id
                where b.user_id = :user_id and a.hashtag is not null group by a.signature
            ) as vw on a.id = vw.signature
            where a.platform_id= :platform_id and a.status_active=1";
        $paramGrandTotal = array(
            'user_id'=>$userId,
            'platform_id'=>$platform_id
        );


        $grand_total = DB_global::cz_select($sqlGrandTotal,$paramGrandTotal,"grand_total");

        // $sqlMultiplier = "Select energy_point from recognize_platform_hdr where id= ?";
        // $paramMultiplier = array(
        //     $platform_id
        // );

        // $multiplier = DB_global::cz_select($sqlMultiplier,$paramMultiplier,"energy_point");

		$sql = "select
						a.signature,a.signature_ind,a.preview_image,a.preview_image_ind,
						round((ifnull(vw.total_score,0) / $grand_total) * 100) as percent,
						ifnull(vw.total_score,0) as total,
						a.html_background_color
					from signature a left join
						(select a.signature,sum(b.point_score) as total_score from behavior a right join
							user_vote b on a.id = b.behavior_id
						where
							b.user_id = ? and a.hashtag is not null
						group by a.signature) as vw on a.id = vw.signature
					where
						(a.is_deleted = 0 and a.status_active = 1) and a.platform_id = ?
					order by
						a.id
        ";

        try {
            //code...
                $param = array(
                    $userId,
                    $platform_id
                );
                #print $sql;
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

    public function InsertComment(Request $request){
        $message = $request->input('message');
        $user_post_id = $request->input('hdnPostId');
        $user_id = $request->input('userId');
        $array_detail = array(
            'user_post_id'=>$user_post_id,
            'user_comment'=>$message,
            'user_created'=>$user_id,
            'date_created'=>DB_global::Global_CurrentDatetime(),
            'user_modified'=>$user_id,
            'date_modified'=>DB_global::Global_CurrentDatetime()
        );

        try {
            //code...
                #print $sql;
                $data = DB_global::cz_insert('user_comment',$array_detail,true);

                $sql = "select a.*,b.name as name, b.profile_picture as profile_picture from user_comment a LEFT JOIN users b ON a.user_created = b.id where a.id = $data";
                $data2 = DB_global::cz_result_array($sql);
                $sql2 = "SELECT user_post_id,COUNT(id) AS total_comment FROM user_comment WHERE status_active = 1 and user_post_id = ? GROUP BY user_post_id";
                $data3 = DB_global::cz_result_array($sql2,[$user_post_id]);
                return response()->json([
                    'data' => $data,
                    'data2'=> $data2,
                    'data3'=> $data3,
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

    public function GenerateLikePost(Request $request){
        $user_id = $request->input('userId');
        $idPost = $request->input('postIdmd5');
        $postIdmd5 = md5($idPost);
        try {
            //code...
            $sql = "SELECT * FROM user_like where md5(user_post_id) = ? and user_created = ?";
            $param=array(
                $postIdmd5,
                $user_id
            );
            $data = DB_global::cz_result_set($sql,$param);
            if(count($data)>0){
                //update
                $flag_like = $data[0]->flag_like;
                $array_data = array(
                    'flag_like'=>($flag_like == 1 ? 0 : 1),
                    'user_modified'=>$user_id,
                    'date_modified'=>DB_global::Global_CurrentDatetime(),
                    );
                    DB_global::cz_update('user_like', 'id', $data[0]->id, $array_data);
            }else{
                //insert
                $sql = "SELECT * FROM user_post where md5(id) = ?";

                $user_post_id = DB_global::cz_select($sql,[$postIdmd5],"id");
                $array_data = array(
                    'user_post_id'=>$user_post_id,
                    'flag_like'=>1,
                    'user_created'=>$user_id,
                    'date_created'=>DB_global::Global_CurrentDatetime(),
                    'user_modified'=>$user_id,
                    'date_modified'=>DB_global::Global_CurrentDatetime()
                );
                $data = DB_global::cz_insert('user_like',$array_data,true);
                $flag_like = 0;
            }

            $sqlTotalLike = "SELECT user_post_id
                                ,COUNT(id) AS total_like
                            FROM user_like
                            WHERE flag_like = 1 and user_post_id = $idPost
                            GROUP BY user_post_id";
            $totalLike = DB_global::cz_result_array($sqlTotalLike);

            return response()->json([
                'data' => $data,
                'data2'=> $flag_like,
                'data3'=> $totalLike,
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

    public function RsListUserComment($userId, $postId){
        // $userId = $request->input("userId");
        // $postId = $request->input("postId");

        $sql = "SELECT
            a.id,
            a.user_comment,
            a.date_created,
            b.name,
            b.id AS user_id,
            IFNULL(comment_detail.flag_like, 0) AS flag_like_detail,
            x2.total_like_comment,b.account,b.profile_picture
        FROM
            user_comment a
                LEFT JOIN
            users b ON a.user_created = b.id
                LEFT JOIN
            user_like AS comment_detail ON comment_detail.user_comment_id = a.id
                AND comment_detail.user_created = :userId
                LEFT JOIN
            (SELECT
                user_comment_id, COUNT(id) AS total_like_comment
            FROM
                user_like
            WHERE
                flag_like = 1
            GROUP BY user_comment_id) AS x2 ON x2.user_comment_id = a.id
        WHERE
            a.status_active = 1
                AND MD5(a.user_post_id) = :postId
        ORDER BY a.date_created ASC";

        // try {
        //     //code...
        //         #print $sql;
                $param =array(
                    'userId'=> $userId,
                    'postId'=> $postId
                );

        $data = DB_global::cz_result_set($sql,$param,false);
        return $data;
        //         return response()->json([
        //             'data' => $data,
        //             'message' => 'success'
        //         ]);

        // } catch (\Throwable $th) {
        //     //throw $th;
        //     return response()->json([
        //         'data' => false,
        //         'message' => 'failed: '.$th
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }

    }

    public function Submit(Request $request){

    }

    public function GetUserDataMentions(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $account = $request->input('account_self');
        //$directorate = $request->input('directorate');

        // $sql = "select id, account, name from users
        // where status_active = 1
        // and directorate is not null
        // and account <> :account
        // and (
        //     (country in (select country from recognize_platform_dtl_1 where platform_id = :platformId1))
        //     or
        //     (directorate in (select directorate from recognize_platform_dtl_2 where platform_id = :platformId2))
        //     )
        // order by id";

        try {

            $country = recognize_platform_dtl_1::select('country')->from('recognize_platform_dtl_1')->where('platform_id','=',$platform_id)->get();

            $directorate = recognize_platform_dtl_2::select('directorate')->from('recognize_platform_dtl_2')->where('platform_id','=',$platform_id)->get();

            $adhoc_user = recognize_platform_dtl_3::select('imdl_id')->from('recognize_platform_dtl_3')
            ->where([
                ['platform_id','=',$platform_id],
                ['flag_active','=',1]
            ])->get();

            $query = User::select('users.id','account','name')
            // ->leftJoin(DB::Raw('(select imdl_id from recognize_platform_dtl_3 where platform_id = '.$platform_id.' and flag_active = 1) as sub'), 'sub.imdl_id', '=', 'users.id')
            ->where([
                ['users.status_active','=',1],
                ['users.account','<>',$account],
                ['users.account','<>',''],
                ['users.email','<>',''],
            ])
            ->whereNotNull('directorate')
            ->when(count($country)>0,
                function($query) use ($country){
                $query->whereIn('country', $country);
            })
            ->when(count($directorate)>0,
                function($query) use ($directorate){
                $query->whereIn('directorate', $directorate);
            })
            ->when(count($adhoc_user)>0,
                function($query) use ($adhoc_user){
                $query->orWhereIn('id', $adhoc_user);
            })
            ->orderBy('users.id', 'asc');
            $data = $query->get();
            $dataPrint = $query->toSql();
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

    public function SubmitUserPost(Request $request)
    {
        $contributor_userid = $request->input('user_id');
        $contributor_user_account = $request->input('user_account');
        $contributor_username = $request->input('user_name');
        $contributor_useremail = $request->input('user_email');
        $content_post = $request->input("post_preview");
        $save_content_post = $content_post;
        $content_email_card = $request->input("email_card");
        $platform_id = $request->input("platform_id");
        $theme_id = $request->input("theme_id");
        $energy_point = $request->input("energy_point");
        $tujuan_upload = 'recognition/uploads';

        $_arrayData = $request->input();
        Log::info('Post Content:', $_arrayData);

        try {
            //code...
            $fileName_upload = '';
            if($request->hasFile('post_image_file')){
                $str = 'Yes, it has file ';
                $file = $request->file('post_image_file');
                $fileName_upload = $contributor_user_account. '_' .$file->getClientOriginalName();
                // $file->move($tujuan_upload, $fileName_upload);
                $fileName_upload = DB_global::cleanFileName($fileName_upload);
                $debugSize = $request->file('post_image_file')->getSize();
                $path = "";
                try {
                    $path = Storage::putFileAs($tujuan_upload, $file, $fileName_upload, 'public');
                    $str .= ' Success upload. ';
                } catch (S3Exception $e) {
                    $str .= ' Failed to upload. '. $e;
                    return response()->json([
                        'data' => false,
                        'message' => 'failed to upload: '.$e
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                Log::info('File name:', [$fileName_upload]);
                Log::info('File size:', [$debugSize]);
                Log::info('File path:', [$path]);

            } else {
                Log::info('No file from the post');
                $str = 'No file from the post';
            }

            $sql_theme = "select img_email_notification,color_mail_name,txt_subject_email_notification  from recognize_theme where platform_id = ? and id = ?";
            $resultTheme =  DB_global::cz_result_array($sql_theme,[$platform_id, $theme_id]);

            $flag_email_card = 0;
            if($content_email_card != '')
            {
                $flag_email_card = 1;
            }

            //validation
            if(strlen($content_post) <= 10)
            {
                //$this->session->set_flashdata('_ActionMessageBox',"Failed to submit");
                $bool_valid_to_submit = false;
                return response()->json([
                    'data' => false,
                    'message' => 'Failed to submit'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $var_point=$energy_point; //---hardcoded score +100
            $bool_valid_to_submit = true;

            //---GET MENTION OF BEHAVIOR TO POST
            preg_match_all('/(^|\s)(@\w+)/', $content_post, $post_mention);
            // preg_match_all('/(^|\s)#([\w-\.]+)/', $content_post, $post_signature);
            preg_match_all('/(^|\s)#([^!\s]+)/', $content_post, $post_signature);
            // preg_match_all('/(^|\s)!([\w-\.]+)/', $content_post, $post_hashtag);
            preg_match_all('/(^|\s)!([^#\s]+)/', $content_post, $post_hashtag);

            //------------------------------------------------------DATA VALIDATION-------------------------------------------------------------

            $total_vote_score = 0;


            $array_user_vote = array();
            $array_user_mention = array();
            $array_user_signature = array();
            foreach ($post_mention[2] as $key_1 => $user_account)
            {   $usr_acc_cek = str_replace("@", "",$user_account);
                $sql = "select * from users where account = ?";
                $data = DB_global::cz_select($sql,[$usr_acc_cek],'id');
                if($data == '')
                {
                    //$this->session->set_flashdata('_ActionMessageBox',"Invalid user account : $user_account, please submit again");
                    $bool_valid_to_submit = false;
                    return response()->json([
                        'data' => false,
                        'message' => "Invalid user account : $user_account, please submit again"
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                if (!array_key_exists($user_account, $array_user_vote) && strtolower($contributor_user_account) != strtolower(str_replace('@', '', $user_account))) {
                    array_push($array_user_vote, $user_account);
                }
                  foreach ($post_hashtag[2] as $key_2 => $save_behavior_hashtag)
                {
                    if (!in_array($save_behavior_hashtag,$array_user_mention))
                    {
                       array_push($array_user_mention, $save_behavior_hashtag);
                    }
                }
                foreach ($post_signature[2] as $key_2 => $save_behavior_signature)
                {
                    if (!in_array($save_behavior_signature,$array_user_signature))
                    {
                       array_push($array_user_signature, $save_behavior_signature);
                    }
                }
            }

            $save_total_user= count($array_user_vote);
            $save_total_behavior= count($array_user_mention);
            $save_total_signature= count($array_user_signature);
            $total_vote_score = ($save_total_user *  $save_total_behavior) * $var_point;

            if($bool_valid_to_submit)
            {
                //--UPLOAD IMAGE IF EXIST ON FILE UPLOAD OBJECT
                if($flag_email_card == 1)
                {
                    $fileupload = $content_email_card;
                }
                else
                {
                    $fileupload = $fileName_upload;
                }
                //--INSERT HEADER
                $array_header = array(
                                    'post_content'=>$save_content_post,
                                    'point_score'=>$total_vote_score,
                                    'total_user'=>$save_total_user,
                                    'total_behavior'=>$save_total_behavior,
                                    'user_created'=>$contributor_userid,
                                    'date_created'=>DB_global::Global_CurrentDatetime(),
                                    'user_modified'=>$contributor_userid,
                                    'date_modified'=>DB_global::Global_CurrentDatetime(),
                                    'upload_image'=>$fileupload,
                                    'flag_email_card'=>$flag_email_card,
                                    'pinned_by'=>'',
                                    'platform_id' => $platform_id,
                            );

                //---INSERT USER POST TABLE
                //$id_header = $this->myClass->InsertHeader($array_header);
                try {
                    $id_header = DB_global::cz_insert('user_post',$array_header,true);
                    // return response()->json([
                    //     'data' => $id_header,
                    //     'message' => 'insert header success'
                    // ]);
                } catch (\Throwable $th) {
                    //throw $th;
                    return response()->json([
                        'data' => false,
                        'message' => 'data insert failed: '.$th
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                $sql_validasi = "select signtag,signtag_ind from signature where is_deleted = 0 and platform_id = ?";
                $rsSignatureValidation = DB_global::cz_result_set($sql_validasi,[$platform_id]);
                // return response()->json([
                //     'rsSignatureValidation' => $rsSignatureValidation,
                //     //'behavior_id' => $behavior_id,
                //     'message' => 'insert header success'
                // ]);
                //---INSERT USER VOTE TABLE BY EXPAND LIST OF USER AND MENTION
                foreach ($post_mention[2] as $key_1 => $user_account)
                {
                    //validation 1 : avoid self voting
                    if(strtolower($contributor_user_account) != strtolower(str_replace('@', '', $user_account)))
                    {

                        $receipient_account = str_replace("@", "",$user_account);
                        $sql_rcpInfo = "select * from users where account = ?";
                        $receipientInfo = DB_global::cz_result_array($sql_rcpInfo,[$receipient_account]);
                          //$receipientInfo = $this->myClass->SelectUserByAccount($receipient_account);
                          $save_user_id = $receipientInfo['id'];
                          $receipient_name = $receipientInfo['name'];
                          $receipient_email = $receipientInfo['email'];

                          $save_content_post = str_replace($user_account, "<span class='inline-user-link'>" . $user_account . "</span>",$save_content_post);

                          foreach ($post_hashtag[2] as $key_2 => $save_behavior_hashtag)
                        {
                            $save_behavior_hashtag = "!".$save_behavior_hashtag;
                            // $param_bhv_id = array(
                            //     'hashtag'=>$save_behavior_hashtag,
                            //     'offset'=>$offset,
                            //     'platform_id'=>$platform_id
                            //     );
                            $sql_bhv_id = 'select * from behavior where (hashtag = ? or hashtag_ind = ?) and platform_id = ?';
                            $behavior_id = DB_global::cz_select($sql_bhv_id,[$save_behavior_hashtag,$save_behavior_hashtag,$platform_id],'id');
                            // return response()->json([
                            //     'sql_bhv_id' => $sql_bhv_id,
                            //     'behavior_id' => $behavior_id,
                            //     'message' => 'insert header success'
                            // ]);
                            $booleanIsUniqe = DB_global::IsUniqueVoteAndValidateData(
                                $id_header,
                                $save_user_id,
                                $behavior_id,
                              $contributor_userid,
                              $platform_id);
                              if($booleanIsUniqe)
                              {
                                  //$save_content_post = str_replace($save_behavior_hashtag, "<span class='inline-user-hashtag'>" . $save_behavior_hashtag . "</span>",$save_content_post);
                                  $array_detail = array(
                                        'user_post_id'=>$id_header,
                                        'user_id'=>$save_user_id,
                                        'behavior_id'=>$behavior_id,
                                        'point_score'=>$var_point,
                                        'recipient_name'=>$receipient_name,
                                        'recipient_email'=>$receipient_email,
                                        'given_by_name'=>$contributor_username,
                                        'given_by_email'=>$contributor_useremail,
                                        'user_created'=>$contributor_userid,
                                        'date_created'=>DB_global::Global_CurrentDatetime(),
                                        'user_modified'=>$contributor_userid,
                                        'date_modified'=>DB_global::Global_CurrentDatetime()
                                        );
                                  //$this->myClass->InsertDetail($array_detail);
                                  try {
                                    $responseInsertDetail = DB_global::cz_insert('user_vote',$array_detail,true);
                                    // return response()->json([
                                    //     'responseInsertDetail' => $responseInsertDetail,
                                    //     'message' => 'insert header success'
                                    // ]);
                                    } catch (\Throwable $th) {
                                        //throw $th;
                                        return response()->json([
                                            'data' => false,
                                            'message' => 'data insert failed: '.$th
                                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                                    }
                            }
                        }
                    }
                }

                //new add fixing by syofian 15102020
                foreach ($post_hashtag[2] as $key_2 => $save_behavior_hashtag)
                {
                    $save_behavior_hashtag = "!".$save_behavior_hashtag;
                    $behavior_id = DB_global::GetBehaviorId($save_behavior_hashtag);
                    $save_content_post = str_replace($save_behavior_hashtag, "<span class='inline-user-hashtag'>" . $save_behavior_hashtag . "</span>",$save_content_post);
                }

                foreach ($post_signature[2] as $key_2 => $save_behavior_signature)
                {
                    $save_behavior_signature = "#".$save_behavior_signature;
                    foreach ($rsSignatureValidation as $signtag)
                    {
                        if ($save_behavior_signature == $signtag->signtag || $save_behavior_signature == $signtag->signtag_ind)
                          {
                            $save_content_post = str_replace($save_behavior_signature, "<span class='inline-user-signature'>" . $save_behavior_signature . "</span>",$save_content_post);
                            break;
                          }
                    }
                }

                //--UPDATE BEAUTY POST CONTENT ON HEADER
                try{
                    DB_global::UpdatePostContent($save_content_post,$id_header);

                    /* process mail in job */
                    // $rsEmail = DB_global::RsEmailNotificationByPostId($id_header, $platform_id);
                    // foreach($rsEmail as $drow)
                    // {
                    //     Log::info('send email to: ',[$drow->receiver_email]);
                    //     $this->sendEmailNotification($id_header,$drow->sender_name,$drow->sender_email,$drow->receiver_name,$drow->receiver_email,$drow->spv_email,$drow->total,$resultTheme['img_email_notification'],$resultTheme['color_mail_name']);
                    // }
                    // Log::info('finish send email');
                    $details = ['id_header' => $id_header,
                                'platform_id' => $platform_id,
                                'img_email_notification' => $resultTheme['img_email_notification'],
                                'color_mail_name' => $resultTheme['color_mail_name'],
                                'txt_subject_email_notification'=> $resultTheme['txt_subject_email_notification']
                                ];
                    SendEmailRecognition::dispatch($details);

                    return response()->json([
                        // 'data' => $rsEmail,
                        'data2' => $str,
                        'message' => 'Thanks for your posting'
                    ]);
                }catch (\Throwable $th) {
                    //throw $th;
                    return response()->json([
                        'data' => false,
                        'message' => 'data insert failed: '.$th
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                //--ALERT POPUP AS SUCCESS CONFIRMATION
                //$this->session->set_flashdata('_ActionMessageBox',MessageLibrary($this,"POST_8"));

                //cznotes 02/04 : fitur email ini menjadi redundance dgn job daily email yang dikirimkan setiap pagi
                // if(ENVIRONMENT=='production')
                // {
                     //$this->GenerateEmailByPostid($id_header);
                // }



                //--RECHECK USER RANKING
                //$this->session->set_userdata('Cz_ranking',$this->myClass->GetUserRanking($this));
            }

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'data post failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function sendEmailNotification($postId,$fromName,$fromEmail,$toName,$toEmail,$spvEmail,$totalPoint,$img_email_notif,$color_mail_name)
    {
        try {
            //code...
            //$recipient = 'mahendra.putra@contracted.sampoerna.com';
        $subject = "Hi ".$toName.", You got " . $totalPoint ." points !";
        $localUrl = env('FRONTEND_URL_RECOGNITION');
        $thriveLink = $localUrl . '?type=received&email='. md5($postId);
        $docPath = env('REACT_APP_USER_DOCUMENT').'theme/'.$img_email_notif;
        $colorMailName = $color_mail_name=='' ||$color_mail_name==null? '#5E1037' : $color_mail_name;
        $imgAttach = file_get_contents($docPath);
        $body = "<html xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns:m='http://schemas.microsoft.com/office/2004/12/omml' xmlns='http://www.w3.org/TR/REC-html40'>

		<head>
			<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=us-ascii'>
			<meta name=Generator content='Microsoft Word 15 (filtered medium)'>
			<![if !mso]><style>v\:* {behavior:url(#default#VML);}
		o\:* {behavior:url(#default#VML);}
		w\:* {behavior:url(#default#VML);}
		.shape {behavior:url(#default#VML);}
		</style><![endif]>
		<style>
		/* Font Definitions */

		@font-face {
			font-family: 'Cambria Math';
			panose-1: 2 4 5 3 5 4 6 3 2 4;
		}

		@font-face {
			font-family: Calibri;
			panose-1: 2 15 5 2 2 2 4 3 2 4;
		}
		/* Style Definitions */

		p.MsoNormal,
		li.MsoNormal,
		div.MsoNormal {
			margin: 0in;
			margin-bottom: .0001pt;
			font-size: 11.0pt;
			font-family: 'Calibri', sans-serif;
		}

		a:link,
		span.MsoHyperlink {
			mso-style-priority: 99;
			color: blue;
			text-decoration: underline;
		}

		a:visited,
		span.MsoHyperlinkFollowed {
			mso-style-priority: 99;
			color: purple;
			text-decoration: underline;
		}

		p.msonormal0,
		li.msonormal0,
		div.msonormal0 {
			mso-style-name: msonormal;
			mso-margin-top-alt: auto;
			margin-right: 0in;
			mso-margin-bottom-alt: auto;
			margin-left: 0in;
			font-size: 11.0pt;
			font-family: 'Calibri', sans-serif;
		}

		span.EmailStyle18 {
			mso-style-type: personal;
			font-family: 'Calibri', sans-serif;
			color: windowtext;
		}

		span.EmailStyle19 {
			mso-style-type: personal-reply;
			font-family: 'Calibri', sans-serif;
			color: windowtext;
		}

		.MsoChpDefault {
			mso-style-type: export-only;
			font-size: 10.0pt;
		}

		@page WordSection1 {
			size: 8.5in 11.0in;
			margin: 1.0in 1.0in 1.0in 1.0in;
		}

		div.WordSection1 {
			page: WordSection1;
		}
		</style>
		<![if gte mso 9]><xml>
		<o:shapedefaults v:ext='edit' spidmax='1028' />
		</xml><![endif]>
		<![if gte mso 9]><xml>
		<o:shapelayout v:ext='edit'>
		<o:idmap v:ext='edit' data='1' />
		</o:shapelayout></xml><![endif]>
		</head>

		<body lang=EN-US link=blue vlink=purple>
		<table class=MsoNormalTable border=0 cellspacing=3 cellpadding=0>
		<tr style='height:654.0pt'>
			<td width=800 valign=top style='width:600.0pt;background:white;padding:.75pt .75pt .75pt .75pt;height:654.0pt'>
				<p class=MsoNormal><span style='color:black'><img width=533 height=581 style='width:5.5555in;height:6.0567in' id='_x0000_i1025' src='cid:my-attach'></span>
					<![if gte vml 1]>
					<v:rect id='_x0000_s1026' style='position:absolute;margin-left:0;margin-top:0;width:400pt;height:436pt;z-index:251657728;mso-position-horizontal-relative:text;mso-position-vertical-relative:text' stroked='f'>
						<v:fill opacity='0' />
						<v:textbox inset='0,0,0,0'>
							<![if !mso]>
							<table cellpadding=0 cellspacing=0 width='100%'>
								<tr>
									<td>
										<![endif]>
										<div>
											<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width=800 style='width:600.0pt;font-family:Calibri,Arial;'>
												<tr style='height:190.0pt'>
													<td width=50 style='width:37.5pt;padding:0in 0in 0in 0in;height:190.0pt'>
														<p class=MsoNormal align=center style='text-align:center'>&nbsp;
															<o:p></o:p>
														</p>
													</td>
													<td width=700 valign=bottom style='width:525.0pt;padding:0in 0in 0in 0in;height:190.0pt'>
														<p class=MsoNormal align=center style='text-align:center'><b><span style='font-size:19.0pt;color:".$colorMailName."'>" . $fromName . " <o:p></o:p></span></b></p>
													</td>
													<td width=50 style='width:37.5pt;padding:0in 0in 0in 0in;height:190.0pt'>
														<p class=MsoNormal align=center style='text-align:center'>&nbsp;
															<o:p></o:p>
														</p>
													</td>
												</tr>
												<tr style='height:195.0pt'>
													<td width=50 style='width:37.5pt;padding:0in 0in 0in 0in;height:195.0pt'>
														<p class=MsoNormal align=center style='text-align:center'>&nbsp;
															<o:p></o:p>
														</p>
													</td>
													<td width=700 style='width:525.0pt;padding:0in 0in 0in 0in;height:195.0pt'>
                                                        <a href='" . $thriveLink . "'>
                                                            <p class=MsoNormal><b><span style='font-size:34.0pt;text-decoration:none'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></b>
                                                                <o:p></o:p>
                                                            </p>
                                                        </a>
													</td>
													<td width=50 style='width:37.5pt;padding:0in 0in 0in 0in;height:195.0pt'>
														<p class=MsoNormal align=center style='text-align:center'>&nbsp;
															<o:p></o:p>
														</p>
													</td>
												</tr>
												<tr style='height:50.0pt'>
													<td width=50 style='width:37.5pt;padding:0in 0in 0in 0in;height:50.0pt'>
														<p class=MsoNormal align=center style='text-align:center'>&nbsp;
															<o:p></o:p>
														</p>
													</td>
													<td width=700 style='width:525.0pt;padding:0in 0in 0in 0in;height:50.0pt'></td>
													<td width=50 style='width:37.5pt;padding:0in 0in 0in 0in;height:50.0pt'>
														<p class=MsoNormal align=center style='text-align:center'>&nbsp;
															<o:p></o:p>
														</p>
													</td>
												</tr>
											</table>
											<p class=MsoNormal><span style='display:none'><o:p>&nbsp;</o:p></span></p>
											<table class=MsoNormalTable border=0 cellspacing=3 cellpadding=0>
												<tr style='height:40.0pt'>
													<td width=800 style='width:600.0pt;padding:.75pt .75pt .75pt .75pt;height:40.0pt'>
														<p class=MsoNormal align=center style='text-align:center'>&nbsp;
															<o:p></o:p>
														</p>
													</td>
												</tr>
											</table>
											<p class=MsoNormal>
												<o:p>&nbsp;</o:p>
											</p>
										</div>
										<![if !mso]>
									</td>
								</tr>
							</table>
							<![endif]>
						</v:textbox>
					</v:rect>
					<![endif]>
					<![if !vml]><span style='mso-ignore:vglayout;position:absolute;z-index:251657728;margin-left:16px;margin-top:147px;width:805px;height:850px'><img width=537 height=585 style='width:5.5902in;height:6.0567in' src='cid:my-attach' alt='Text Box:   	". $fromName ."	  &#13;&#10;  	                   &#13;&#10;  &#13;&#10;  		  &#13;&#10;  &#13;&#10;  	". $fromName ."	  &#13;&#10;  	                   &#13;&#10;  &#13;&#10;  		  &#13;&#10;&#13;&#10;' v:shapes='_x0000_s1026'></span>
					<![endif]>
					<o:p></o:p>
				</p>
			</td>
		</tr>
		</table>
		</body>

		</html>";
        /*$configurationSet = 'ConfigSet';*/

        $mail = new PHPMailer(true);

        // try {
            // Specify the SMTP settings.
            $mail->isSMTP();
            //$mail->setFrom($fromEmail, $fromName);
            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->Host       = env('MAIL_HOST');
            $mail->Port       = env('MAIL_PORT');
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'tls';
            /*$mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);*/
            $mail->addAddress($toEmail, $toName);
            $mail->addStringEmbeddedImage($imgAttach, 'my-attach', 'map.png', 'base64', 'image/png');
            $mail->isHTML(true);
            $mail->Subject    = $subject;
            $mail->Body       = $body;
            if($spvEmail != ''){
                $mail->AddCC($spvEmail);
            }
            //$mail->AltBody    = $bodyText;
            $mail->Send();
            $array_log = array('sender_name'=>$fromName,
				'subject'=>$subject,
				'email_to'=>$toEmail,
				'email_body'=>$body,
				'flag_email'=>1,
				'date_email'=>DB_global::Global_CurrentDatetime(),
				'transaction_id'=>$postId);
			DB_global::InsertEmailLog($array_log);
            //return true;
        // } catch (phpmailerException $e) {
        //     //echo "An error occurred. {$e->errorMessage()}", PHP_EOL; //Catch errors from PHPMailer.
        //     return response()->json([
        //         'data' => "error",
        //         'message' => 'failed: '.$e
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // } catch (Exception $e) {
        //     echo "Email not sent. {$mail->ErrorInfo}", PHP_EOL; //Catch errors from Amazon SES.
        //     return response()->json([
        //         'data' => "false",
        //         'message' => 'failed: '.$th
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'email failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
