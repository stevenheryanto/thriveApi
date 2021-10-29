<?php

namespace App\Http\Controllers\Findtime;

use DB_global;
use App\Models\Findtime\ideation_challenge;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChallengeController extends Controller
{
    protected $table_name = 'ideation_challenge';
    protected $folder_name = 'ideation/challenge';

    public function ListData(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $platform_id = $request->input('platform_id');
        $status_active = $request->input('status_active');

        try {
            $query = ideation_challenge::select('ideation_challenge.*',
                'b.name as user_created_name',
                'c.name as user_modified_name'
                )
                ->leftJoin('users as b', 'b.id', '=', 'ideation_challenge.user_created')
                ->leftJoin('users as c', 'c.id', '=', 'ideation_challenge.user_modified')
                ->where('platform_id', '=', $platform_id)
                ->where('is_deleted', '=', 0);
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('ideation_challenge.publish_flag', 'desc')
                    ->orderBy('ideation_challenge.status_active', 'desc')
                    ->orderBy('ideation_challenge.sort_index', 'asc')
                    ->get();                
                $tempObj = new \ArrayObject();
                foreach($data as $drow):
                    $cleanDescr = strip_tags($drow->description);
                    $readMore = DB_global::StringReadMore($cleanDescr, 400);
                    $drow->shortdescr = $readMore;
                    $tempObj->append($drow);
                endforeach;
                $data = $tempObj;
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

    public function SelectData(Request $request)
    {
        $id = $request->input('md5ID');
        $sql = "select * from $this->table_name where md5(id) = ? limit 1";

        try {
            $data = DB_global::cz_result_array($sql,[$id]);

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

    public function SelectChallenge(Request $request)
    {
        /* SelectBusinessChallengeById need id */
        /* SelectListBusinessChallenge does not need id */
        $platform_id = $request->input('platform_id');
        $id = $request->input('id');
        try {
            $query = ideation_challenge::select('*',
                DB::RAW('IFNULL(sub_z.total_idea, 0) AS total_ideas'),
                DB::RAW('IFNULL(sub_x.total_comment, 0) AS total_comment'),
                DB::RAW('IFNULL(sub_y.total_like, 0) AS total_likes')
                )
                ->leftJoin(DB::RAW('(SELECT b.business_challenge,
                        COUNT(a.id) AS total_comment
                        FROM ideation_user_comment a
                        LEFT JOIN ideation_user_post b
                        ON a.user_post_id = b.id
                    WHERE a.status_active = 1
                    AND b.status_active = 1
                    GROUP BY b.business_challenge) AS sub_x'),
                    'sub_x.business_challenge', '=', 'x.id'
                )
                ->leftJoin(DB::RAW('(SELECT b.business_challenge,
                        COUNT(a.id) AS total_like
                        FROM ideation_user_like a
                        LEFT JOIN ideation_user_post b
                        ON a.user_post_id = b.id
                    WHERE flag_like = 1
                    AND b.status_active = 1
                    GROUP BY b.business_challenge) AS sub_y'),
                    'sub_y.business_challenge', '=', 'x.id'
                )
                ->leftJoin(DB::RAW('(SELECT business_challenge
                        COUNT(id) AS total_idea
                        FROM ideation_user_post
                    WHERE status_active = 1
                    GROUP BY business_challenge) AS sub_z'),
                    'sub_z.business_challenge','=', 'x.id')
                ->where('platform_id', '=', $platform_id)
                ->when(isset($id), function ($query) use($id) {
                    $query->where('ideation_challenge.id','=', $id);
                })
                ->when(is_null($id), function ($query) {
                    $query->where('ideation_challenge.is_deleted','=', 0)
                    ->where('ideation_challenge.publish_flag','=', 1)
                    ->orderBy('ideation_challenge.status_active', 'desc')
                    ->orderBy('ideation_challenge.sort_index', 'desc');
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

    public function GetStringSeperatedCommas(Request $request)
	{
        $challenge_id = $request->input('challenge_id');
		$sql = "SELECT directorate FROM ideation_challenge_directorate WHERE challenge_id = ?";
        try {
            $rs = DB_global::cz_result_set($sql, [$challenge_id]);
            $strFunction = '';
            foreach($rs as $drow):
                $strFunction = str_replace('~', ', ', $strFunction) . $drow->func . '~';
            endforeach;
            $data = str_replace('~', '', $strFunction);
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

    public function RsSlider(Request $request)
    {
        $where = $request->input("platform");
        $param = [];
        $str_where="";
        if($where!=""){
            $str_where= "and platform_id = ?";
            $param = array(
                $where
            );
        }

		$sql = "select * from $this->table_name where is_deleted = 0 and challenge_image is not null and status_active = 1 ".$str_where." order by name";

        try {
            $data = DB_global::cz_result_set($sql,$param);

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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();

        $file = $request->file('challenge_file');
        $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
        $fileName = DB_global::cleanFileName($fileName);
        Storage::putFileAs($this->folder_name, $file, $fileName, 'public');
        unset($_arrayData['user_account'] , $_arrayData['challenge_file']); 
        $_arrayData = array_merge($_arrayData,
        array(
            'challenge_image' => $fileName,
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

    public function InsertDirectorate(Request $request)
    {
        /* InsertFunctionDetail */
        $_arrayData = $request->input();
        $_arrayData = array_merge($_arrayData,
        array(
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ));
        try {
            $data = DB_global::cz_insert('ideation_challenge_directorate', $_arrayData,false);
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

    public function DeleteDirectorate(Request $request)
    {
        /* ResetFunctionDetail */
        $challenge_id = $request->input('challenge_id');
        try {
            $hdr = DB_global::cz_delete('ideation_challenge_directorate', 'challenge_id', $challenge_id);
            return response()->json([
                'data' => true,
                'message' => 'data delete success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data delete failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListDirectorate(Request $request)
    {
        /* ListBusinessChallengeFunction */
        $challenge_id = $request->input('challenge_id');
        $sql = "SELECT * FROM ideation_challenge_directorate WHERE challenge_id = ? ";
        try {
            $data = DB_global::cz_result_array($sql,[$challenge_id]);
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

    public function UpdateData(Request $request)
    {
        $id = $request->input('id');
        $all = $request->except('user_account','id','challenge_file');

        if($request->hasFile('challenge_file'))
        {
            $file = $request->file('challenge_file');
            $fileName = $request->input('user_account'). '_' .$file->getClientOriginalName();
            $fileName = DB_global::cleanFileName($fileName);
            Storage::putFileAs($this->folder_name, $file, $fileName, 'public');    
            $all = array_merge($all, ['challenge_image' => $fileName]);
        }
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name, 'id', $id, $all);

            return response()->json([
                'data' => true,
                'message' => 'data update success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data update failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ValidateId(Request $request)
    {
        $id = $request->input('id');
        try {
            $data = DB_global::bool_ValidateDataOnTableById_md5($this->table_name, $id);
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

    public function DeleteData(Request $request)
    {
        $id = $request->input('id');
        try {
            $param = array(
                'is_deleted'=>1
            );
            $hdr = DB_global::cz_update($this->table_name, 'id', $id, $param);

            return response()->json([
                'data' => true,
                'message' => 'data delete success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'data delete failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function MoveUp(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');

        $sql = "update $this->table_name set sort_index = sort_index + 1
            where is_deleted = 0
            and (sort_index >= (:sort_index - 1) and sort_index <> :sort_index2)
            and platform_id = :platform_id";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index,
            'platform_id' => $platform_id
        ];

        $sql2 = "update $this->table_name set sort_index = sort_index - 1
            where is_deleted = 0
            and id = :id
            and platform_id = :platform_id";
        $param2 = ['id'=>$id,
            'platform_id'=>$platform_id
        ];
        try {
            $data = DB_global::cz_execute_query($sql, $param);
            $data2 = DB_global::cz_execute_query($sql2, $param2);
            $this->ReSortingIndex($platform_id);

            return response()->json([
                'data' => $data,
                'data2' => $data2,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function MoveDown(Request $request)
	{
        $id = $request->input('id');
        $sort_index = $request->input('sort_index');
        $platform_id = $request->input('platform_id');

        $sql = "update $this->table_name set sort_index = sort_index - 1
            where is_deleted = 0
            and (sort_index <= (:sort_index + 1) and sort_index <> :sort_index2)
            and platform_id = :platform_id";
        $param = ['sort_index' => $sort_index,
            'sort_index2' => $sort_index,
            'platform_id' => $platform_id
        ];
        $sql2 = "update $this->table_name set sort_index = sort_index + 1
            where is_deleted = 0
            and id = :id
            and platform_id = :platform_id ";
        $param2 = ['id'=>$id,
            'platform_id'=>$platform_id
        ];
        try {
            $data = DB_global::cz_execute_query($sql, $param);
            $data2 = DB_global::cz_execute_query($sql2, $param2);
            $this->ReSortingIndex($platform_id);

            return response()->json([
                'data' => $data,
                'data2' => $data2,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	function ReSortingIndex($platform_id)
	{
        DB::statement(DB::raw('set @rownum = 0'));
        DB::table($this->table_name)
            ->where('platform_id', $platform_id)
            ->where('is_deleted', 0)
            ->orderBy('sort_index', 'asc')
            ->update([
                'sort_index' => DB::raw('@rownum := @rownum + 1'),
            ]);
	}
}
