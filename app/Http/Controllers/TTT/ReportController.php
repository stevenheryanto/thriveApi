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
use App\Exports\IndividualRecordExport;
use App\Exports\MeetingScoreExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use App\Models\TTT\Timetothink_rating;

class ReportController extends Controller{
    
    public function ListDataRecord(Request $request)
    {

        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');

        try{
            $query = Timetothink_rating::select(
            DB::raw("DATE_FORMAT(timetothink_rating.date_created, '%H:%i') submit_time"),
            'timetothink_rating.id',
            'timetothink_rating.subject',
            'timetothink_rating.user_organizer',
            'timetothink_rating_participant.comment as participant_comment',
            'timetothink_rating.score_rating AS summary_score_rating',
            'timetothink_rating.status_enable',
            'timetothink_rating.status_active',
            'timetothink_rating.user_created',
            'timetothink_rating.date_created',
            DB::raw("DATE_FORMAT(timetothink_rating.date_created, '%d %M %Y') meeting_date"),
            'timetothink_rating.user_modified',
            'timetothink_rating.date_modified',
            'timetothink_rating.is_deleted',
            'd.id as participant_id',
            'd.name as participant_name',
            'timetothink_rating_participant.score_rating',
            'b.name as user_organizer_name'
            )
            ->join('users as b', 'b.id', '=', 'timetothink_rating.user_organizer')
            ->join('timetothink_rating_participant', 'timetothink_rating_participant.rating_id', '=', 'timetothink_rating.id')
            ->join('users as d', 'd.id', '=', 'timetothink_rating_participant.user_participant')
            ->WHERE('timetothink_rating.status_active', '=', '1') 
            ->WHERE('timetothink_rating.flag_draft', '=', '0') 
            ->WHERE('timetothink_rating.platform_id', '=', $platform_id)
            ->WHERE('timetothink_rating_participant.platform_id', '=', $platform_id)
            ->when(!is_null($filter_search) , function ($query) use($filter_search) {
                $query->where('timetothink_rating.subject','like', '%'.$filter_search.'%')
                ->orWhere('timetothink_rating.comment','like', '%'.$filter_search.'%')
                ->orWhere('b.name','like', '%'.$filter_search.'%')
                ->orWhere('timetothink_rating.score_rating','like', '%'.$filter_search.'%');
            })
            ->when(!is_null($filter_period_from) && !is_null($filter_period_to),
             function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(timetothink_rating.date_created, date)'), [$filter_period_from, $filter_period_to]);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->get();
            } else {
                $data = $query->count();
            }
            
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

    public function ListDataScore(Request $request){
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');

        try{
            $query = Timetothink_rating::select(
            DB::raw("DATE_FORMAT(timetothink_rating.date_created, '%H:%i') submit_time"),
            'timetothink_rating.id',
            'timetothink_rating.subject',
            'timetothink_rating.user_organizer',
            'timetothink_rating.comment',
            'timetothink_rating.score_rating AS summary_score_rating',
            'timetothink_rating.status_enable',
            'timetothink_rating.status_active',
            'timetothink_rating.user_created',
            'timetothink_rating.date_created',
            DB::raw("DATE_FORMAT(timetothink_rating.date_created, '%d %M %Y') meeting_date"),
            'timetothink_rating.user_modified',
            'timetothink_rating.date_modified',
            'timetothink_rating.is_deleted',
            'users.name as user_organizer_name',
            'timetothink_rating_participant.total_participant'
            )
            ->join('users', 'users.id', '=', 'timetothink_rating.user_organizer')
            ->join(DB::raw('(
                select distinct count(user_participant) as total_participant,rating_id,platform_id 
                    from 
                        timetothink_rating_participant 
                    group by rating_id,platform_id
            ) as timetothink_rating_participant'), 'timetothink_rating.id', '=', 'timetothink_rating_participant.rating_id')
            ->WHERE('timetothink_rating.status_active', '=', '1') 
            ->WHERE('timetothink_rating.flag_draft', '=', '0') 
            ->WHERE('timetothink_rating.platform_id', '=', $platform_id)
            ->WHERE('timetothink_rating_participant.platform_id', '=', $platform_id)
            ->when(!is_null($filter_search) , function ($query) use($filter_search) {
                $query->where('timetothink_rating.subject','like', '%'.$filter_search.'%')
                ->orWhere('timetothink_rating.comment','like', '%'.$filter_search.'%')
                ->orWhere('users.name','like', '%'.$filter_search.'%')
                ->orWhere('timetothink_rating.score_rating','like', '%'.$filter_search.'%');
            })
            ->when(!is_null($filter_period_from) && !is_null($filter_period_to),
            function ($query) use ($filter_period_from, $filter_period_to) {
                $query->whereBetween(DB::raw('convert(timetothink_rating.date_created, date)'), [$filter_period_from, $filter_period_to]);
            });

            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->get();
            } else {
                $data = $query->count();
            }
            
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

    public function FormExport(Request $request)
    {
        ini_set('max_execution_time', 180);
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $dateStamp =  date('Ymdhms');
        $module_name = $request->input('module_name');
        $report_name = $request->input('report_name');
        try {
            $folder_name = 'think/temp/';
            $excelName = 'report_'.$report_name.'_'.$dateStamp. '.xlsx';

            if($report_name === 'record'){  
                Excel::store(new IndividualRecordExport( $platform_id, $filter_search, $filter_period_from, $filter_period_to), $folder_name.$excelName);
            }else{ 
                Excel::store(new MeetingScoreExport($platform_id, $filter_search, $filter_period_from, $filter_period_to), $folder_name.$excelName);
            }
                $path = Storage::path($folder_name.$excelName);
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            return Storage::get($path, 200, $headers);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'export failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
