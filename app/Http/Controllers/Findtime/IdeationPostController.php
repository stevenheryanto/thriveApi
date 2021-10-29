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

class IdeationPostController extends Controller
{
    protected $table_name = 'ideation_user_post';
    protected $folder_name = 'ideation/post';

    public function InsertData(Request $request)
    {
        /* InsertHeader */
        $_arrayData = $request->input();

        $file = $request->file('attachment_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['attachment_file']); 
        $_arrayData = array_merge($_arrayData,
        array(
            'attachment_filename' => $fileName,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert($this->table_name, $_arrayData,false);
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

    public function ListComment(Request $request)
    {
        /* RsLoadMorePostComment */
        $limit = $request->input('limit');
        $offset = $request->input('offset');

        try {
            $data = ideation_user_comment::select('*')
                ->offset($offset)
                ->limit($limit)
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'data' => $data,
                'message' => 'data load success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data load failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function FlagAsPinned(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = ideation_user_post::where('id', $id)->update(['pinned_flag' => 1]);
            return response()->json([
                'data' => $data,
                'message' => 'pinned success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'pinned failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function RevokePinned(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = ideation_user_post::where('id', $id)->update(['pinned_flag' => 0]);
            return response()->json([
                'data' => $data,
                'message' => 'revoke success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'revoke failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListData(Request $request)
    {
        /* ListDataTab1 (Post) does not use pinned_flag */
        /* ListDataTab2 (Pinned) uses pinned_flag */
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $filter_search = $request->input('filter_search');
        $filter_period_from = $request->input('filter_period_from');
        $filter_period_to = $request->input('filter_period_to');
        $pinned_flag = $request->input('pinned_flag');

        try {
            $query = ideation_user_post::select(
                'c.name',
                'c.account',
                'ideation_user_post.*',
                'b.name AS bc_name',
                'b.description AS bc_description'
            )
            ->leftJoin('ideation_challenge as b', 'ideation_user_post.business_challenge', '=', 'b.id')
            ->leftJoin('users as c', 'ideation_user_post.user_created', '=', 'c.id')
            ->where('ideation_user_post.platform_id', '=', $platform_id)
            ->where('b.flag_voting', '=', 1)
            ->whereNotNull('c.id')
            ->when(($pinned_flag == 1), 
                function ($query) {
                $query->where('ideation_user_post.pinned_flag','=', 1);
            })
            ->when(isset($filter_search), 
                function ($query) use($filter_search) {
                $query->where('b.name','like', '%'.$filter_search.'%')
                ->orWhere('b.description','like', '%'.$filter_search.'%')
                ->orWhere('b.campaign_type','like', '%'.$filter_search.'%')
                ->orWhere('b.challenger_name','like', '%'.$filter_search.'%')
                ->orWhere('c.name','like', '%'.$filter_search.'%')
                ->orWhere('c.account','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.id','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_name','like', '%'.$filter_search.'%')
                ->orWhere('ideation_user_post.idea_description','like', '%'.$filter_search.'%');
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
}