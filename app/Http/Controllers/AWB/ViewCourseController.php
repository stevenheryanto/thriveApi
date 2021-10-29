<?php

namespace App\Http\Controllers\AWB;

use AwbGlobal;
use DB_Global;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailAwbShareCourse;
use App\Jobs\SendEmailAwbRegisterCourse;
use App\Models\AWB\awb_mst_config;
use App\Models\AWB\awb_mst_course;
use App\Models\AWB\awb_mst_slider_sff;
use App\Models\AWB\awb_trn_category;
use App\Models\AWB\awb_trn_course;
use App\Models\AWB\awb_trn_course_share;
use App\Models\AWB\awb_users_info;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;


class ViewCourseController extends Controller
{
    public function getCategory(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            $category_mst_course = awb_mst_course::select('category_id')->where([['flag_active','=', 1],['platform_id','=',$platform_id]]);
            $data = awb_trn_category::select(DB::raw('md5(id) as pageId'), 'title', 'category_image')
                    ->where([['flag_active','=', 1],['platform_id','=',$platform_id]])
                    ->whereIn('id',$category_mst_course)
                    ->orderBy('title', 'asc')->get();
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

    public function getSlider(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            $data = awb_mst_slider_sff::where([['flag_active','=', 1],['platform_id','=',$platform_id]])
                    ->orderBy('seqnum', 'asc')->get();

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

    public function getActivePeriod(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            $data = AwbGlobal::getActivePeriodInternal($platform_id);
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

    public function ListCourse(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        $category_id = $request->input('md5id');
        $course_type = $request->input("course_type");
        $course_id = $request->input('course');
        try {
            $min = awb_users_info::where('id', '=', $user_id)->first();
            if(isset($min)){
                $userGrade = $min->group_grade;
                $userYos = $min->group_yos;
            } else {
                $userGrade = NULL;
                $userYos = NULL;
            }
            if($userGrade == 'SG 01-06' or $userGrade == 'SG 07-09' or $userGrade == NULL){
                $minGrade = 0;
            } else {
                $minGrade = 1;
            }
            if($userYos == '<=1' or $userYos == '<1' or $userYos == NULL){
                $minYos = 0;
            } else {
                $minYos = 1;
            }
            $subCourse = awb_trn_course::select('course_id', 'date_created')
                ->where('user_created', '=', $user_id)
                ->where('platform_id', '=', $platform_id);
            $activePeriod = AwbGlobal::getActivePeriodInternal($platform_id);

            $query = DB::table('awb_mst_course as a')->selectRaw("a.*,
                    b.title as category_title,
                    FLOOR(a.duration_amt / 4 / 7 / 24 / 60 / 60 ) as months,
                    (FLOOR(a.duration_amt / 7 / 24 / 60 / 60 ) % 4) as weeks,
                    (FLOOR(a.duration_amt / 24 / 60 / 60 ) % 7) as days,
                    (FLOOR(a.duration_amt / 60 / 60) % 24) as hours,
                    (FLOOR(a.duration_amt / 60) % 60) as minutes,
                    (a.duration_amt % 60) as seconds,
                    CASE WHEN t.date_created is not null THEN 1 ELSE 0 end as flag_registered
                ")
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->leftJoinSub($subCourse, 't', function ($join){
                    $join->on('t.course_id', '=', 'a.id');
                })
                ->where([
                    ['a.flag_active', '=', 1],
                    ['b.flag_active', '=', 1],
                    ['a.group_grade', '<=', $minGrade],
                    ['a.group_yos', '<=', $minYos],
                    ['a.platform_id', '=', $platform_id],
                ])
                ->when(isset($category_id), function($query) use($category_id){
                    $query->where(DB::raw('md5(b.id)'), '=', $category_id);
                })
                ->when(isset($course_id), function($query) use($course_id){
                    $query->where(DB::raw('md5(a.id)'), '=', $course_id);
                }, function($query) use($course_type){
                    $query->where('a.course_type','=',$course_type);
                })
                ->when($activePeriod == NULL, function($query) {
                    $query->orderBy('a.course_type', 'asc')
                        ->orderBy('a.id', 'desc');
                }, function($query){
                    $query->orderBy('a.course_type', 'desc')
                        ->orderBy('a.id', 'desc');
                });
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

    public function getCourseDetail(Request $request){
        $id = $request->input('id');
        try {
            //code...
            $data = awb_mst_course::where('id', '=', $id)->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => [],
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validateShareCourse(Request $request){
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $id = $request->input('articleIdmd5');
        $platform_id = $request->input('platform_id');
        try {
            //code...
            if(
            awb_trn_course_share::where([
                ['course_id', '=', $id],
                ['user_id', '=', $user_id],
                ['platform_id', '=', $platform_id]
            ])->exists()){
                $data= false;
                $configShareArticle = 0;
            }else{
                $data = true;
                $configShareArticle = awb_mst_config::where('_code', '=', 'SHARE_ARTICLE')
                    ->where('platform_id', '=', $platform_id)
                    ->value('value');
            };
            return response()->json([
                'data' => $data,
                'point' => $configShareArticle,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => [],
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function SubmitShareCourse(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        $user_id  = $userData->id;
        $trn_course_id = $request->input('trn_article_id');
        $participant = $request->input('participant');
        $arrParticipant = json_decode($participant);
        $configSendEmail = awb_mst_config::where('_code', '=', 'email_notification')->where('platform_id', '=', $platform_id)->value('value');
        try {
            //code...
            $listParticipant = [];
            if(count($arrParticipant) > 0){
                foreach($arrParticipant as $partDtl){
                    $listParticipant = array_merge($listParticipant, [$partDtl->value]);
                }
                $rsListUser = DB::table('users')->select('id',
                        'email',
                        DB::raw('ifnull(name,full_name) as name')
                    )->where('status_active', '=', 1)
                    ->whereIn('id', $listParticipant)
                    ->get();
                $listEmail = '';
                foreach ($rsListUser as $drow)
                {
                    $listEmail = str_replace('~',',',$listEmail) . $drow->email . '~';
                }
                $array_data = array(
                    'course_id'=> $trn_course_id,
                    'user_id'=>$user_id,
                    'points'=>0,
                    'share_email_to'=>str_replace('~','',$listEmail),
                    'share_date'=>DB_global::Global_CurrentDatetime(),
                    'flag_email'=>($configSendEmail == 'TRUE' ? 1 : 0),
                    'platform_id' => $platform_id,
                );
                DB_global::cz_insert('awb_trn_course_share', $array_data);

                $arrayPointHistory = ([
                    'user_id' => $user_id,
                    'point' => 0,
                    'source' => 'share article bonus point',
                    'status_date' => DB_global::Global_CurrentDatetime(),
                    'user_modified' => $user_id,
                    'date_modified' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id,
                ]);
                DB_global::cz_insert('awb_trn_point_history', $arrayPointHistory);
                AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);

                $courseDetail = awb_mst_course::where('id', '=', $trn_course_id)->get();

                /* send email */
                if($configSendEmail == 'TRUE')
                {
                    foreach ($rsListUser as $drow)
                    {
                        if(isset($drow->email)){
                            $details = [
                                'toEmail' => $drow->email,
                                'toName' => $drow->name,
                                'userName'=> $userData->name,
                                'courseTitle' => $courseDetail[0]->title,
                                'courseUrl' => env('FRONTEND_URL_LEARN').'viewcourse_detail?category='.md5($courseDetail[0]->category_id).'&course='.md5($courseDetail[0]->id),
                                'courseDescription' => str_replace("'","",$courseDetail[0]->description),
                                'courseId' => $trn_course_id
                            ];
                            SendEmailAwbShareCourse::dispatch($details);
                        }
                    }
                }

            }
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => [],
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function registerCourse(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $course_id = $request->input('course_id');
        $platform_id = $request->input('platform_id');
        $configSendEmail = awb_mst_config::where('_code', '=', 'email_notification')->where('platform_id', '=', $platform_id)->value('value');
        try {
            $activePeriod = AwbGlobal::getActivePeriodInternal($platform_id);
            /* count how many courses the user had registered during the period */
            $userRegisteredList = DB::table('awb_trn_course as t')
                ->leftJoin('awb_mst_course as m', 'm.id', '=', 't.course_id')
                ->where([
                    ['m.flag_active', '=', 1],
                    ['m.course_type', '<>', 1],
                    ['t.user_created', '=', $user_id],
                    ['t.platform_id', '=', $platform_id]
                ])
                ->when(isset($activePeriod), function($query) use($activePeriod){
                    $query->where('t.reg_period', '=', $activePeriod['id']);
                })
                ->count();
            /* check whether employee have reached maximum number of courses allowed */
            if($activePeriod['allow_course'] == $userRegisteredList){
                $data = false;
            } else {
                $arrayData = [
                    'course_id' => $course_id,
                    'reg_period' => $activePeriod['id'],
                    'flag_active' => 1,
                    'user_created' => $user_id,
                    'date_created' => DB_global::Global_CurrentDatetime(),
                    'user_modified' => $user_id,
                    'date_modified' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id
                ];
                DB_global::cz_insert('awb_trn_course', $arrayData);
                /* send email */
                if($configSendEmail == 'TRUE')
                {
                    $details = [
                        'course_id' => $course_id,
                        'toEmail' => $userData->email,
                        'toName' => $userData->full_name,
                        'platform_id' => $platform_id
                    ];
                    SendEmailAwbRegisterCourse::dispatch($details);
                }
                $data = true;
            }
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => "failed $th"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
