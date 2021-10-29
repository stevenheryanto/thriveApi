<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Models\TTR\User;
use App\Models\TTR\recognize_platform_dtl_3;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Exports\RawDataExport;
use App\Exports\RawDataExport2;
use App\Models\TTR\User_post;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function TopContributors(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filter_hashtag = $request->input('filter_hashtag');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $param = [
            'platform_id' => $platform_id
        ];

        $str_where = ' ';
        if(!empty($startDate) && !empty($endDate))
        {
            $str_where =  $str_where." and date(a.date_created) between :startDate and :endDate ";
            $param = array_merge($param,['startDate'=>$startDate,'endDate'=>$endDate]);
        }

        $filter_hashtag = ($filter_hashtag == "all" || $filter_hashtag == "") ? "" : "and hashtag in (".$filter_hashtag.")";

        $sql = "SELECT
            name, email, account, id, department as dept_text,title as position_text, SUM(score) AS score,profile_picture
        FROM
            (SELECT
                b.name,
                    b.email,
                    b.account,
                    b.id,
                    b.department,
                    b.title,
                    sum(a.point_score) AS score, 'data_real_count' as 'source',b.profile_picture
            FROM
                    user_vote a LEFT JOIN
                    users b ON a.user_created = b.id LEFT JOIN
                    behavior c on c.id = a.behavior_id
                WHERE
                    a.status_active = 1
                    AND b.status_active = 1
                    and c.hashtag is not null
                    and c.platform_id = :platform_id
                    $filter_hashtag
                    $str_where
                GROUP BY b.name, b.email, b.account,b.id,b.department,b.title,b.profile_picture
            ) x
        WHERE
            x.score > 0
        GROUP BY name , email , account , id,profile_picture
        ORDER BY score DESC , name";


		$offset = ((isset($offset) && $offset <> "") ? $offset : 0);
		if ($category != "COUNT" && $export == false)
		{
			$sql = $sql . " LIMIT  $offset,$limit ";
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

    public function TopReceivers(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filter_hashtag = $request->input('filter_hashtag');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $param = [
            'platform_id' => $platform_id
        ];

        $str_where = ' ';
        if(!empty($startDate) && !empty($endDate))
        {
            $str_where =  $str_where." and date(a.date_created) between :startDate and :endDate ";
            $param = array_merge($param,['startDate'=>$startDate,'endDate'=>$endDate]);
        }

        $filter_hashtag = ($filter_hashtag == "all" || $filter_hashtag == "") ? "" : "and hashtag in (".$filter_hashtag.")";

        $sql = "select *
        from
        (
            select  b.name, b.email, b.account,b.id,b.department as dept_text,b.title as position_text,
                sum(a.point_score) as score,b.profile_picture
            from user_vote a left join
                users b on a.user_id = b.id left join
                behavior c on a.behavior_id = c.id left join
                signature d on c.signature = d.id and c.platform_id = d.platform_id
            where
                a.status_active = 1
                and b.status_active = 1
                and c.hashtag is not null
                and c.platform_id = :platform_id
                $filter_hashtag
                $str_where
            group by b.name, b.email, b.account,b.id,b.department,b.title,b.profile_picture
        ) x
        where x.score > 0
        order by x.score desc, x.name";


		$offset = ((isset($offset) && $offset <> "") ? $offset : 0);
		if ($category != "COUNT" && $export == false)
		{
			$sql = $sql . " LIMIT  $offset,$limit ";
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

    public function RsListBehavior()
	{

    }

    public function RsReceiversBehaviorTotal(Request $request)
	{
        $userId = $request->input('userId');
        $behavior_hashtag = $request->input('behavior_hashtag');
        $platform_id = $request->input('platform_id');

        $sql = "select c.hashtag, count(a.id) as total
                    from user_vote a
                    left join behavior c on a.behavior_id = c.id
					where a.user_id = :userId
                    and hashtag = :behavior_hashtag
                    and c.platform_id = :platform_id
					group by c.hashtag ";
        try {
            $param = array(
                'userId'=>$userId,
                'behavior_hashtag'=>$behavior_hashtag,
                'platform_id'=>$platform_id
            );
            $data = DB_global::cz_select($sql,$param,"total");

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

    public function RsContributorsBehaviorTotal(Request $request)
	{
        $userId = $request->input('userId');
        $behavior_hashtag = $request->input('behavior_hashtag');
        $platform_id = $request->input('platform_id');

		$sql = "select c.hashtag, count(a.id) as total
                    from user_vote a
                    left join behavior c on a.behavior_id = c.id
					where a.user_created = :userId
                    and hashtag = :behavior_hashtag
                    and c.platform_id = :platform_id
                    group by a.hashtag ";

        try {
            $param = array(
                'userId'=>$userId,
                'behavior_hashtag'=>$behavior_hashtag,
                'platform_id'=>$platform_id
            );
            $data = DB_global::cz_select($sql,$param,"total");

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

	public function RsListBehaviorFilter(Request $request)
	{
        $platform_id = $request->input('platform_id');

		$sql = "select hashtag as name
                from signature a
                left join behavior b on b.signature =a.id
                where a.status_active = 1
                    and a.is_deleted = 0
                    and a.platform_id = :platform_id
                    and b.status_active = 1
                    and b.is_deleted = 0
                    and b.platform_id = a.platform_id
                order by hashtag";

        try {
            $data = DB_global::cz_result_set($sql,[$platform_id]);

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

	public function BehaviorUsage(Request $request)
	{
        $platform_id = $request->input('platform_id');

        $sql = "
            SELECT x. *
			FROM (
				select @rownum := @rownum +1 as rank, hashtag,score from
			    (
				   select b.hashtag,
						count(a.id) AS score
                    from user_vote a
                    left join behavior b on a.behavior_id = b.id
					where a.status_active = 1
                        and b.status_active = 1
                        and b.platform_id = ?
					group by b.hashtag
			    ) as x, (SELECT @rownum :=0)r
			    where score > 0
			    order by score desc
			) as x
        ";

        try {
            $data = DB_global::cz_result_set($sql,[$platform_id]);

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

    public function Report1(Request $request)
    {

        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filterMonth = $request->input('filterQuarter');
        $filterYear = $request->input('filterYear');

        if($where !="")
	 	{
            $str_where = " and (
                a.id like :id
                or b.recipient_email like :recipient_email
                or b.recipient_name like :recipient_name
                or b.given_by_email like :given_by_email
                or b.given_by_name like :given_by_name
            )";
            $param = array(
                'id'=> '%'.$where.'%',
                'recipient_email'=> '%'.$where.'%',
                'recipient_name'=> '%'.$where.'%',
                'given_by_email'=> '%'.$where.'%',
                'given_by_name'=> '%'.$where.'%',
            );
        } else {
            $str_where = "";
            $param = [];
        }


        if($filterMonth !="")
	 	{
            $str_filterMonth = " and MONTH(a.date_created) = :month";
            $param =  array_merge($param,
            array(
                'month'=> $filterMonth
            ));
        } else {
            $str_filterMonth = "";
        }

        if($filterYear !="")
	 	{
            $str_filterYear = " and YEAR(a.date_created) = :year";
            $param =  array_merge($param,
            array(
                'year'=> $filterYear
            ));
        } else {
            $str_filterYear = "";
        }


        $sql = "
        SELECT a.id
            ,b.recipient_email
            ,b.recipient_name
            ,b.point_score AS point
            ,b1.hashtag AS behavior
            ,c1.signtag AS signature
            ,a.post_content AS comment
            ,b.given_by_email
            ,b.given_by_name
            ,a.date_created
            ,ifnull(y1.total_comment, 0) AS total_comment
            ,ifnull(z1.total_like, 0) AS total_like
            ,a.upload_image
            ,x.email AS on_behalf_by_email
            ,x.name AS on_behalf_by_name
            ,a.flag_on_behalf_of
        FROM user_post a
        LEFT JOIN user_vote b ON a.id = b.user_post_id
        LEFT JOIN behavior b1 ON b.behavior_id = b1.id
        LEFT JOIN signature c1 ON b1.signature = c1.id
        LEFT JOIN users x ON a.user_onbehalf_by = x.id
        LEFT JOIN (
            SELECT user_post_id
                ,count(id) AS total_comment
            FROM user_comment
            GROUP BY user_post_id
            ) y1 ON a.id = y1.user_post_id
        LEFT JOIN (
            SELECT user_post_id
                ,count(id) AS total_like
            FROM user_like
            GROUP BY user_post_id
            ) z1 ON a.id = z1.user_post_id
        WHERE b1.id IS NOT NULL
            AND a.platform_id = :platform_id
            $str_filterMonth
            $str_filterYear
            $str_where
            order by a.id desc";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);


        try {
            if ($category != "COUNT" && $export == false)
            {
                $sql = $sql . " LIMIT  :offset,:limit ";
                //code...
                $param = array_merge($param,
                array(
                'limit'=>$limit,
                'offset'=>$offset,
                'platform_id'=>$platform_id
                ));
            }else{
                //code...
                $sql = "
                SELECT a.id
                FROM user_post a
                LEFT JOIN user_vote b ON a.id = b.user_post_id
                LEFT JOIN behavior b1 ON b.behavior_id = b1.id
                LEFT JOIN signature c1 ON b1.signature = c1.id
                LEFT JOIN users x ON a.user_onbehalf_by = x.id
                LEFT JOIN (
                    SELECT user_post_id
                        ,count(id) AS total_comment
                    FROM user_comment
                    GROUP BY user_post_id
                    ) y1 ON a.id = y1.user_post_id
                LEFT JOIN (
                    SELECT user_post_id
                        ,count(id) AS total_like
                    FROM user_like
                    GROUP BY user_post_id
                    ) z1 ON a.id = z1.user_post_id
                WHERE b1.id IS NOT NULL
                    AND a.platform_id = :platform_id
                    $str_filterMonth
                    $str_filterYear
                    $str_where
                    order by a.id desc";

                $param = array_merge($param,
                array(
                    'platform_id'=>$platform_id
                ));
            }
            #print $sql;
            $data = DB_global::cz_result_set($sql,$param,false,$category);


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

    public function Report2(Request $request)
    {
        $where = $request->input('str_where');

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $platform_id = $request->input('platform_id');
        $chkCountry = DB_global::cz_result_set('SELECT country FROM recognize_platform_dtl_1 WHERE platform_id=:platform_id', [$platform_id]);
        $chkDirectorate = DB_global::cz_result_set('SELECT directorate FROM recognize_platform_dtl_2 WHERE platform_id=:platform_id', [$platform_id]);
        $adhoc_user = recognize_platform_dtl_3::select('imdl_id')->from('recognize_platform_dtl_3')
        ->where([
            ['platform_id','=',$platform_id],
            ['flag_active','=',1]
        ]);

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
        // print_r($arrCountry);
        try{
            $query = User::select(
                'users.id','users.name','users.business_unit','users.title','users.business_unit'
                ,'users.directorate','users.division_p', 'users.division_q','users.department'
                ,'z.receivers','y.contributor'
            )
            ->leftJoin(DB::raw('(
                select a.user_created as id, sum(a.point_score) as contributor
                from
                    user_vote a
                group by a.user_created
            ) as y'), 'users.id', '=', 'y.id')
            ->leftJoin(DB::raw('(
                select a.user_id as id, sum(a.point_score) as receivers
                    from
                        user_vote a
                    group by a.user_id
            ) as z'), 'users.id', '=', 'z.id')
            ->where(function ($query) {
                $query->where('z.receivers', '>', 0)
                    ->orWhere('y.contributor', '>', 0);
            })
            ->when(count($arrDirectorate)>0, function ($query) use ($arrDirectorate){
                $query->whereIn('users.directorate', $arrDirectorate);
            })
            ->when(count($arrCountry)>0, function ($query) use ($arrCountry){
                $query->whereIn('users.country', $arrCountry);
            })
            ->orWhereIn('users.id', $adhoc_user)
            ->when(!is_null($where), function ($query) use($where) {
                $query->where('users.id','like', '%'.$where.'%')
                ->orWhere('users.name','like', '%'.$where.'%')
                ->orWhere('users.account','like', '%'.$where.'%')
                ->orWhere('users.email','like', '%'.$where.'%');
            })
            ->orderBy('z.receivers', 'desc')
            ->orderBy('y.contributor', 'desc');
            // ->toSql();
            // echo $query;
            // exit();
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('account')
                    ->get();
            } else {
                $data = $query->count();
            }
            // dd(DB::getQueryLog());
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);

        // $sql = "
		// 		select x.id,x.name,x.business_unit,x.title,
		// 			x.business_unit, x.directorate, x.division_p, x.division_q, x.department,
		// 			z.receivers,y.contributor
		// 		from users x left join
		// 		(
		// 			select a.user_created as id, sum(a.point_score) as contributor
		// 			from
		// 				user_vote a
		// 			group by a.user_created
		// 		) as y on x.id = y.id left join
		// 		(
		// 			select a.user_id as id, sum(a.point_score) as receivers
		// 			from
		// 				user_vote a
		// 			group by a.user_id
		// 		) as z on x.id = z.id
        //         where (z.receivers > 0 or y.contributor > 0)
        //         and x.directorate in (:directorate)
        //         and x.country in (:country)
        //         $str_where
		// 		order by z.receivers desc, y.contributor desc
		// ";

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function RawDataFormExport(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $tabtype = $request->input('tabtype');
        $where = $request->input('where');
        $month = $request->input('filterQuarter');
        $year = $request->input('filterYear');
        $dateStamp =  date('Ymdhms');

        try {
            $folder_name = 'recognition/temp/';
            $excelName = 'report_rawdata_'.$dateStamp. '.xlsx';

            // Excel::store(new PinnedExport($platform_id, $tabtype, $where), $excelName);
            // $path = Storage::path($excelName);
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '999999');
            ini_set('hard_timeout', 99999);
            ini_set('max_input_time', '99999');
            if($tabtype == 'report1'){
                Excel::store(new RawDataExport($platform_id, $where, $year, $month), $folder_name.$excelName);
            }else{
                Excel::store(new RawDataExport2($platform_id, $where),  $folder_name.$excelName);
            }
            $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            // return response()->download($path, $excelName, $headers)->deleteFileAfterSend(false);
            return Storage::get($path, 200, $headers);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function RawDataFormExportBackup(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $tabtype = $request->input('tabtype');
        $where = $request->input('where');
        $month = $request->input('filterQuarter');
        $year = $request->input('filterYear');
        // $dateStamp =  date('Ymdhms');
        $page = $request->query('page');

        try {
            if($tabtype == 'report1'){

                $data = User_post::query()
                ->select('user_post.id'
                ,'user_vote.recipient_name'
                ,'user_vote.recipient_email'
                ,'user_vote.point_score'
                ,'behavior.hashtag AS behaviour'
                ,'signature.signtag AS signature'
                ,strip_tags('user_post.post_content')
                ,'user_post.date_created'
                ,'user_vote.given_by_name'
                ,'user_vote.given_by_email'
                ,DB::raw('ifnull(b1.total_comment, 0) AS total_comment')
                ,DB::raw('ifnull(c1.total_like, 0) AS total_like')
                ,'user_post.upload_image'
                ,'a.account AS sender_account'
                ,'b.account AS recipient_account'
                )
                ->leftJoin('user_vote', 'user_vote.user_post_id' , '=' , 'user_post.id')
                ->leftJoin('behavior', 'behavior.id' , '=' , 'user_vote.behavior_id')
                ->leftJoin('signature', 'signature.id' , '=' , 'behavior.signature')
                ->join('users as a',DB::raw('a.id'),'=',DB::raw('user_post.user_created'))
                ->join('users as b',DB::raw('b.id'),'=',DB::raw('user_vote.user_id'))
                ->leftJoin(DB::raw('(select user_post_id, count(id) as total_comment from user_comment
                group by user_post_id) as b1'), 'user_post.id', '=', 'b1.user_post_id')
                ->leftJoin(DB::raw('(select user_post_id, count(id) as total_like from user_like
                group by user_post_id ) as c1'), 'user_post.id', '=', 'c1.user_post_id')
                ->where('user_post.platform_id', '=', $platform_id)
                ->whereNotNull('behavior.id')
                ->when(!is_null($month), function ($query) use ($month) {
                    $query->whereMonth('user_post.date_created', $month);
                })
                ->when(!is_null($year), function ($query) use ($year) {
                    $query->whereYear('user_post.date_created', $year);
                })
                ->when(!is_null($where), function ($query) use ($where){
                    $query->where('user_post.id','like', '%'.$where.'%')
                    ->orWhere('user_vote.recipient_email','like', '%'.$where.'%')
                    ->orWhere('user_vote.recipient_name','like', '%'.$where.'%')
                    ->orWhere('user_vote.given_by_email','like', '%'.$where.'%')
                    ->orWhere('user_vote.given_by_name','like', '%'.$where.'%');
                })
                ->orderBy('user_post.id', 'desc')
                ->orderBy('b.account', 'asc');
            }else{
                $chkCountry = DB_global::cz_result_set('SELECT country FROM recognize_platform_dtl_1 WHERE platform_id=:platform_id', [$platform_id]);
                $chkDirectorate = DB_global::cz_result_set('SELECT directorate FROM recognize_platform_dtl_2 WHERE platform_id=:platform_id', [$platform_id]);
                $adhoc_user = recognize_platform_dtl_3::select('imdl_id')->from('recognize_platform_dtl_3')
                ->where([
                    ['platform_id','=',$platform_id],
                    ['flag_active','=',1]
                ]);
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

                $data = User::query()
                ->select('users.id as IMDL_ID','users.name','users.account as windows_account','users.business_unit','users.title','users.business_unit'
                    ,'users.directorate','users.division_p', 'users.division_q','users.department'
                    ,'z.receivers','y.contributor'
                )
                ->leftJoin(DB::raw('(
                    select a.user_created as id, sum(a.point_score) as contributor
                    from
                        user_vote a
                    group by a.user_created
                ) as y'), 'users.id', '=', 'y.id')
                ->leftJoin(DB::raw('(
                    select a.user_id as id, sum(a.point_score) as receivers
                        from
                            user_vote a
                        group by a.user_id
                ) as z'), 'users.id', '=', 'z.id')
                ->where(function ($query) {
                    $query->where('z.receivers', '>', 0)
                        ->orWhere('y.contributor', '>', 0);
                })
                // ->whereIn('users.directorate', $arrDirectorate)
                // ->whereIn('users.country', $arrCountry)
                ->when(count($arrDirectorate)>0, function ($query) use ($arrDirectorate) {
                    $query->whereIn('users.directorate', $arrDirectorate);
                })
                ->when(count($arrCountry)>0, function ($query) use ($arrCountry){
                    $query->whereIn('users.country', $arrCountry);
                })
                ->orWhereIn('users.id', $adhoc_user)
                ->when(!is_null($where), function ($query) use($where){
                    $query->where('users.id','like', '%'.$where.'%')
                    ->orWhere('users.name','like', '%'.$where.'%')
                    ->orWhere('users.account','like', '%'.$where.'%')
                    ->orWhere('users.email','like', '%'.$where.'%');
                })
                ->orderBy('z.receivers', 'desc')
                ->orderBy('y.contributor', 'desc');
            }

            if(isset($page)){
                $data = $data->simplePaginate(5000);
            }else{
                $data = $data->paginate(5000);
            }

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
