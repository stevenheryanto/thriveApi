<?php

namespace App\Http\Controllers\TTR;

use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CharityController extends Controller
{
    protected $table_name = 'recognize_charity';

    public function ListData(Request $request)
    {
        $limit = $request->input('limit');
        $offset = $request->input('offset');
        $category = $request->input('category');
        $export = $request->input('export');

        $platform_id = $request->input('platform_id');
        $sql = "select *,
            round(point_target / point_required_to_claim_the_item,0) as target_item
            from $this->table_name where is_deleted = 0 and platform_id = :platform_id order by period_start";

        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT :offset, :limit  ";

            $param = array(
                'platform_id' => $platform_id,
                'limit' => $limit,
                'offset' => $offset
            );
        } else {
            $param = array(
                'platform_id' => $platform_id
            );
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

    public function InsertData(Request $request)
    {
        $_arrayData = $request->input();
        $_arrayData = array_merge($_arrayData,[
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'date_modified'=> DB_global::Global_CurrentDatetime()
        ]);
        try {
            $data = DB_global::cz_insert($this->table_name,$_arrayData,false);
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

    public function UpdateData(Request $request)
    {
        $id = $request->input('id');
        $all = $request->except('id');
        $all = array_merge($all, ['date_modified' => DB_global::Global_CurrentDatetime()]);
        try {
            $data = DB_global::cz_update($this->table_name,'id',$id,$all);
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
            $data = DB_global::bool_ValidateDataOnTableById_md5($this->table_name,$id);
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
                'is_deleted'=>1,
                'flag_active' => 0
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

    public function GetActiveCharity(Request $request){
        $platform_id = $request->input('platform_id');
        try {
            $sql = "select * ,
                round(point_target / point_required_to_claim_the_item,0) as target_item,
                datediff(period_end,now()) as remaining_day
            from recognize_charity
            where flag_active = 1 and program_type = 'book' and is_deleted=0 and platform_id = ? order by period_end";
            $hasil = DB_global::cz_result_array($sql,[$platform_id]);
            if(count($hasil) > 0){
                $sqlTotalPoint ="select sum(b.point_score) as total_point
                from behavior a right join
                    user_vote b on a.id = b.behavior_id  left join
                    users c on b.user_id = c.id
                where
                    a.hashtag is not null
                    and convert(b.date_created,date) between :period_start and :period_end
                    and c.status_active = 1 and a.platform_id = :platform_id";
                $param = array(
                    'platform_id'=>$platform_id,
                    'period_start'=>$hasil['period_start'],
                    'period_end'=>$hasil['period_end']
                );

                $totalPoint = DB_global::cz_select($sqlTotalPoint,$param,"total_point");
                $hasil = array_merge(array('collected_point'=>$totalPoint),$hasil);
            }

            return response()->json([
                'data' => $hasil,
                'message' => 'success'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GetActiveCharity2(Request $request){
        $platform_id = $request->input('platform_id');
        try {
            $sql = "select * ,
                round(point_target / point_required_to_claim_the_item,0) as target_item,
                datediff(period_end,now()) as remaining_day
            from recognize_charity
            where flag_active = 1 and program_type = 'money' and is_deleted=0 and platform_id = ? order by period_end";
            $hasil = DB_global::cz_result_array($sql,[$platform_id]);
            if(count($hasil) > 0){
                $sqlTotalPoint ="select sum(b.point_score) as total_point
                from behavior a right join
                    user_vote b on a.id = b.behavior_id  left join
                    users c on b.user_id = c.id
                where
                    a.hashtag is not null
                    and convert(b.date_created,date) between :period_start and :period_end
                    and c.status_active = 1 and a.platform_id = :platform_id";
                $param = array(
                    'platform_id'=>$platform_id,
                    'period_start'=>$hasil['period_start'],
                    'period_end'=>$hasil['period_end']
                );

                $totalPoint = DB_global::cz_select($sqlTotalPoint,$param,"total_point");
                $hasil = array_merge(array('collected_point'=>$totalPoint),$hasil);
            }

            return response()->json([
                'data' => $hasil,
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
