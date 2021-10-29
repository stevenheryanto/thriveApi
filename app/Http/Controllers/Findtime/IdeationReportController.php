<?php

namespace App\Http\Controllers\Findtime;

use DB_global;
use App\Exports\IdeationRawdataExport;
use App\Exports\IdeationChallengeExport;
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

class IdeationReportController extends Controller
{
    public function ListDataRawdata(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');

        try {
            $query = ideation_user_post::select(
                'ideation_user_post.id',
                'ideation_user_post.idea_name',
                'ideation_user_post.idea_description',
                'ideation_user_post.attachment_filename',
                'ideation_user_post.idea_status',
                'ideation_user_post.posting_date',
                'b.name AS bc_name',
                'b.description AS bc_description',
                'b.validity_period_from',
                'b.validity_period_to',
                'b.campaign_type',
                'b.challenger_name',
                'c.name AS uc_name',
                'c.account AS uc_account',
                'c.email AS email',
                'c.id AS employee_id',
                DB::RAW('ifnull(sub_x.total_comment, 0) AS total_comment'),
                DB::RAW('ifnull(sub_y.total_like, 0) AS total_like'),
                'c.directorate',
                'c.business_unit',
                'ideation_user_post.hackathon',
                'ideation_user_post.location',
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
            ->where('ideation_user_post.status_active', '=', 1)
            ->where('ideation_user_post.platform_id', '=', $platform_id)
            ->where('b.is_deleted', '=', 0)
            ->when(isset($filter_search), 
                function ($query) use($filter_search) {
                $query->where('b.name','like', '%'.$filter_search.'%')
                ->orWhere('b.description','like', '%'.$filter_search.'%')
                ->orWhere('b.campaign_type','like', '%'.$filter_search.'%')
                ->orWhere('b.challenger_name','like', '%'.$filter_search.'%')
                ->orWhere('c.name','like', '%'.$filter_search.'%')
                ->orWhere('c.account','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_name','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_description','like', '%'.$filter_search.'%')
                ;
            })
            ->when(isset($filter_period_from) && isset($filter_period_to),
                function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(ideation_user_post.posting_date, date)'), [$filter_period_from, $filter_period_to]);
            });
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('ideation_user_post.id', 'desc')
                    ->get();
                    // ->toSQL();
                    // dd($query);
                    // exit();
        
            } else {
                $data = $query->count();
            }
            
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

    public function ListDataChallenge(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');

        try {
            $query = ideation_user_post::select(
                'ideation_user_post.id',
                'ideation_user_post.idea_name',
                'ideation_user_post.idea_description',
                'ideation_user_post.attachment_filename',
                'ideation_user_post.idea_status',
                'ideation_user_post.posting_date',
                'b.name AS bc_name',
                'b.description AS bc_description',
                'b.validity_period_from',
                'b.validity_period_to',
                'b.campaign_type',
                'b.challenger_name',
                'c.name AS uc_name',
                'c.account AS uc_account',
                'c.email AS email',
                'c.id AS employee_id',
                DB::RAW('ifnull(sub_x.total_comment, 0) AS total_comment'),
                DB::RAW('ifnull(sub_y.total_like, 0) AS total_like'),
                'c.directorate',
                'c.business_unit',
                DB::RAW('case when b.status_active = 1 and b.flag_voting = 0 
                        then "Active" 
                    when b.status_active = 1 and b.flag_voting = 1 
                        then "Voting"
                    when b.status_active = 0 
                        then "Inactive" else "" 
                    end AS bc_status'),
                DB::RAW('case when b.flag_voting = 1 
                    then b.voting_period_from 
                    else null 
                    end AS voting_period_from'),
                DB::RAW('case when b.flag_voting = 1 
                    then b.voting_period_to 
                    else null 
                    end AS voting_period_to')
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
            ->where('ideation_user_post.status_active', '=', 1)
            ->where('ideation_user_post.platform_id', '=', $platform_id)
            ->where('b.is_deleted', '=', 0)
            ->where('b.flag_voting', '=', 1)
            ->when(isset($filter_search), 
                function ($query) use($filter_search) {
                $query->where('b.name','like', '%'.$filter_search.'%')
                ->orWhere('b.description','like', '%'.$filter_search.'%')
                ->orWhere('b.campaign_type','like', '%'.$filter_search.'%')
                ->orWhere('b.challenger_name','like', '%'.$filter_search.'%')
                ->orWhere('c.name','like', '%'.$filter_search.'%')
                ->orWhere('c.account','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_name','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_description','like', '%'.$filter_search.'%')
                ;
            })
            ->when(isset($filter_period_from) && isset($filter_period_to),
                function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(ideation_user_post.posting_date, date)'), [$filter_period_from, $filter_period_to]);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('ideation_user_post.id', 'desc')
                    ->get();
            } else {
                $data = $query->count();
            }

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

    public function FormExport(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $flag_voting = $request->input('flag_voting');

        $dateStamp =  date('Ymdhms');
        try {
            $folder_name = 'ideation/temp/';

            if($flag_voting == 1){
                $excelName = 'report_challenge_'.$dateStamp. '.xlsx';
                Excel::store(new IdeationChallengeExport($platform_id, $filter_search, $filter_period_from, $filter_period_to), $folder_name.$excelName);
            } else {
                $excelName = 'report_rawdata_'.$dateStamp. '.xlsx';
                Excel::store(new IdeationRawdataExport($platform_id, $filter_search, $filter_period_from, $filter_period_to),  $folder_name.$excelName);
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
}