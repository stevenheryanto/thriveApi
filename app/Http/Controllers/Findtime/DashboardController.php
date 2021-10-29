<?php

namespace App\Http\Controllers\Findtime;

use DB_global;
use App\Models\Findtime\User;
use App\Models\Findtime\ideation_challenge;
use App\Models\Findtime\ideation_user_comment;
use App\Models\Findtime\ideation_user_like;
use App\Models\Findtime\ideation_user_post;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function RsListNewsfeed(Request $request)
    {

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $user_created = $request->input('user_created');
        /* user_created used by RsListNewsfeedMyIdeas */
        $user_liked = $request->input('user_liked');
        /* user_liked is mandatory */
        $challenge_id = $request->input('challenge_id');
        $case = $request->input('case');
        /* case:
            1 RsListNewsfeed
            2 RsListNewsfeedMostLiked 
            3 RsListNewsfeedMostCommented 
        */
        $subLike = ideation_user_like::select(
                'user_post_id',
                DB::RAW('MAX(flag_like) AS flag_like')
            )
            ->where('user_created', '=', $user_liked)
            ->groupBy('user_post_id');

        $query = ideation_user_post::select(
            'ideation_user_post.id',
            'ideation_user_post.idea_name',
            'ideation_user_post.idea_description',
            'ideation_user_post.attachment_filename',
            'ideation_user_post.date_created',
            'ideation_user_post.user_created',
            'b.id AS bc_id',
            'b.name AS bc_name',
            'b.description AS bc_description',
            'b.validity_period_from',
            'b.validity_period_to',
            'b.challenge_image',
            'b.status_active',
            'c.name',
            'c.account',
            'c.profile_picture',
            DB::RAW('ifnull(sub_x.total_comment, 0) AS total_comment'),
            DB::RAW('ifnull(sub_y.total_like, 0) AS total_like'),
            DB::RAW('ifnull(z.bc_total_ideas,0) AS bc_total_ideas'),
            DB::RAW('ifnull(z.bc_total_comment,0) AS bc_total_comment'),
            DB::RAW('ifnull(z.bc_total_likes,0) AS bc_total_likes'),
            'lk.flag_like AS current_user_flag_like'
        )
        ->leftJoin('ideation_challenge as b', 'ideation_user_post.business_challenge', '=', 'b.id')
        ->leftJoin('users as c', 'ideation_user_post.user_created', '=', 'c.id')
        ->leftJoin(DB::RAW('(SELECT 
            user_post_id, 
            COUNT(id) AS total_comment
            FROM ideation_user_comment
            WHERE status_active = 1
            GROUP BY user_post_id) as sub_x'),
            'sub_x.user_post_id', '=', 'ideation_user_post.id')
        ->leftJoin(DB::RAW('(SELECT 
            user_post_id, 
            COUNT(id) AS total_like
            FROM ideation_user_like
            WHERE flag_like = 1
            GROUP BY user_post_id) as sub_y'),
            'sub_y.user_post_id', '=', 'ideation_user_post.id')
        ->joinSub($subLike, 'lk', function ($join){
            $join->on('lk.user_post_id','=','ideation_user_post.id');
        })
        ->leftJoin(DB::RAW('(SElECT aa.id as business_challenge, 
                COUNT(bb.id) as bc_total_ideas,
                SUM(sub_xx.total_comment) as bc_total_comment,
                SUM(sub_yy.total_like) as bc_total_likes  
            FROM ideation_challenge aa 
            LEFT JOIN ideation_user_post bb on aa.id = bb.business_challenge
            LEFT JOIN 
                (SELECT user_post_id, COUNT(id) AS total_comment 
                FROM ideation_user_comment WHERE status_active = 1 
                GROUP BY user_post_id) AS sub_xx 
                ON sub_xx.user_post_id = bb.id 
            LEFT JOIN
                (SELECT user_post_id, COUNT(id) AS total_like
                FROM ideation_user_like WHERE flag_like = 1 
                GROUP BY user_post_id) AS sub_yy 
                ON sub_yy.user_post_id = bb.id 
            group by aa.id
            ) as z'),
            'z.business_challenge','=','b.id')
        ->where('ideation_user_post.status_active', '=', 1)
        ->where('ideation_user_post.platform_id', '=', $platform_id)
        ->where('ideation_user_post.publish_flag', '=', 1)
        ->where('b.is_deleted', '=', 0)
        ->when(isset($challenge_id),
            function ($query) use($challenge_id) {
                $query->where('b.id','=', $challenge_id);
            }
        )
        ->when(isset($user_created),
            function ($query) use($user_created) {
                $query->where('ideation_user_post.user_created','=', $user_created);
            }
        )
        ->when(isset($filter_search), 
            function ($query) use($filter_search) {
                $query->where('b.name','like', '%'.$filter_search.'%')
                ->orWhere('c.name','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_name','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_description','like', '%'.$filter_search.'%');
            }
        )
        ->when($case == 1,
            function ($query) use($case){
                $query->orderBy('ideation_user_post.id', 'desc');
            }
        )
        ->when($case == 2,
            function ($query) use($case){
                $query->orderBy('sub_y.total_like', 'desc')
                    ->orderBy('ideation_user_post.id', 'desc');
            }
        )
        ->when($case == 3,
            function ($query) use($case){
                $query->orderBy('sub_x.total_comment', 'desc')
                    ->orderBy('ideation_user_post.id', 'desc');
            }
        );

        try {
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->get();
    
            } else {
                $data = $query->count();
            }
            return response()->json([
                'data' => $data,
                'message' => 'load data success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'load data failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GenerateLikePost(Request $request)
    {
        $_arrayData = $request->input();
        $user_created = $request->input('user_created');
        $user_post_id = $request->input('user_post_id');
        try {
            if(ideation_user_like::where('user_post_id', '=', $user_post_id)
                ->where('user_created', '=', $user_created)
                ->exist()){
                    $flag_like = ideation_user_like::where('user_post_id', '=', $user_post_id)
                        ->where('user_created', '=', $user_created)
                        ->value('flag_like');
                    $data = ideation_user_like::where('user_post_id', $user_post_id)
                        ->where('user_created', '=', $user_created)
                        ->update([
                            'flag_like' => ($flag_like == 1 ? 0 : 1),
                            'user_modified' => $user_created,
						    'date_modified' => DB_global::Global_CurrentDatetime()
                    ]);
            } else {
                $_arrayData = array_merge($_arrayData,[
                    'flag_like' => 1,
                    'date_created'=> DB_global::Global_CurrentDatetime(),
                    'date_modified'=> DB_global::Global_CurrentDatetime()
                ]);
                $data = DB_global::cz_insert('ideation_user_like', $_arrayData, false);
            };
            return response()->json([
                'data' => true,
                'message' => 'generate like success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'generate like failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function InsertComment(Request $request)
    {
        $_arrayData = $request->input(); 
        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert('ideation_user_comment', $_arrayData,false);
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

    public function RsListUserComment(Request $request)
    {
        $user_post_id = $request->input('user_post_id');
        $user_created = $request->input('user_created');
        $query = ideation_user_comment::select(
            'ideation_user_comment.id',
            'ideation_user_comment.user_comment',
            'ideation_user_comment.date_created',
            'ideation_user_comment.id',
            'b.name',
            'b.id AS user_id',
            'b.account',
            'b.profile_picture',
            DB::RAW('IFNULL(l.flag_like, 0) AS flag_like_detail'),
            'l2.total_like_comment',
            )        
        ->leftJoin('users as b', 'ideation_user_comment.user_created', '=', 'b.id')
        ->leftJoin('ideation_user_like as l', 'l.user_comment_id', '=', 'ideation_user_comment.id')
        ->leftJoin(DB::RAW('(SELECT user_comment_id, 
        `       COUNT(id) AS total_like_comment
                FROM ideation_user_like
                WHERE flag_like = 1
                GROUP BY user_comment_id) 
                AS l2'),
                'l2.user_comment_id','=','ideation_user_comment.id')
        ->where('ideation_user_comment.status_active','=', 1)
        ->where('ideation_user_comment.user_post_id','=', $user_post_id)
        ->where('l.user_created','=', $user_created)
        ->orderBy('ideation_user_comment.date_created', 'desc');

        try {
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'load data success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'load data failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}