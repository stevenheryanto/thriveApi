<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use App\Http\Controllers\Controller;
use App\Imports\AWB\ArticleImport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;

class ArticleImportController extends Controller
{
    public function ListData(Request $request)
    {	
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');
        $offset = $request->input('offset');
        $limit = $request->input('limit');
        
        try {
            $query = DB::table('awb_trn_article_import as a')
                ->selectRaw('a.*, b.name as user_created_name')
                ->leftJoin('users as b', 'a.user_created', '=', 'b.id')
                ->where('a.platform_id', '=', $platform_id);
            if($category != "COUNT"){
                $data = $query->offset($offset)
                    ->limit($limit)
                    ->orderBy('a.id', 'DESC')
                    ->get();
            } else {
                $data = $query->count();
            }
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ResetData(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try{
            DB::table('awb_trn_article_import_detail_temp')->where('platform_id', '=', $platform_id)->delete();
            DB::table('awb_trn_article_import_detail')->where('platform_id', '=', $platform_id)->delete();
            DB::table('awb_trn_article_import')->where('platform_id', '=', $platform_id)->delete();
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ImportData(Request $request)
    {
        $token = $request->bearerToken();
        $userData = JWTAuth::toUser($token);
        $platform_id = $request->input('platform_id');
        try {
            $configImportLike = 0;
            $configImportComment = 0;
            $configImportShare = 0;
            $configImportView = 0;
            /* ConfigList */
            $rsConfig = DB::table('awb_mst_config')->where('_code', 'like', 'IMPORT_%')->where('platform_id', '=', $platform_id)->get();
            foreach($rsConfig as $drow){
                switch($drow->_code)
                {
                    case "IMPORT_POINT_COMMENT":
                        $configImportComment = (int)$drow->value;
                        break;
                    case "IMPORT_POINT_LIKE":
                        $configImportLike = (int)$drow->value;
                        break;
                    case "IMPORT_POINT_SHARE":
                        $configImportShare = (int)$drow->value;
                        break;
                    case "IMPORT_POINT_VIEW":
                        $configImportView = (int)$drow->value;
                        break;
                }
            }

            $totalData = 0;
            $fileName = '';
            if($request->hasFile('article_file'))
            {
                $file = $request->file('article_file');
                $fileName = $userData->account. '_' .$file->getClientOriginalName();
                $fileName = DB_global::cleanFileName($fileName);
                Storage::putFileAs('learn/article_import', $file, $fileName, 'public');
            }
            $arrayImport = array(
				'article_name' => $fileName,
				'flag_active' => 1,
				'user_created' => $userData->id,
				'date_created' => DB_Global::Global_CurrentDatetime(),
				'user_modified' => $userData->id,
				'date_modified' => DB_Global::Global_CurrentDatetime(),
                'platform_id' => $platform_id
			);
            $trn_article_import_id = DB_Global::cz_insert('awb_trn_article_import', $arrayImport, TRUE);
            Excel::import(new ArticleImport($trn_article_import_id, $platform_id), $file);
            $totalData = DB::table('awb_trn_article_import_detail_temp')
                ->where('trn_article_import_id', '=', $trn_article_import_id)
                ->where('platform_id', '=', $platform_id)
                ->count();

            /* ImportFilteredData */
            $rs = $this->ImportFilteredData($trn_article_import_id, $platform_id);
			$totalValid = count($rs);
			foreach($rs as $drow){
				// insert into actual table import
				$arrayDetail = [
					'trn_article_import_id'=> $drow->trn_article_import_id,
					'article_action'=> trim(strtoupper($drow->article_action)),
					'article_id' => trim($drow->article_id),
					'user_employee_id' => trim($drow->user_employee_id),
					'user_employee_email' => $drow->user_employee_email,
					'user_employee_name' => $drow->user_employee_name,
					'user_employee_function' => $drow->user_employee_function,
					'content_datetime' => $drow->content_datetime,
					'article_comment' => $drow->article_comment,
					'total_redudance' => $drow->total_redudance,
					'platform_id' => $platform_id,
                ];
				$insert = DB_global::cz_insert('awb_trn_article_import_detail', $arrayDetail);
			}

            // insert into table point history
			$rs = $this->GenerateUserPoint($trn_article_import_id, $platform_id);
			foreach($rs as $drow){
				$pointAddition = 0;
				$caseAction = trim(strtoupper($drow->article_action));
				switch($caseAction)
				{
					case "COMMENT":
						$pointAddition = $configImportComment;
						break;
					case "LIKE":
						$pointAddition = $configImportLike;
						break;
					case "SHARE":
						$pointAddition = $configImportShare;
						break;
					case "VIEW":
						$pointAddition = $configImportView;
						break;
				}
				$arrayHistory = array(
					'user_id' => $drow->user_employee_id,
					'point' => $pointAddition * $drow->total_activity,
					'source' => "point addition from " . $caseAction . " activity (total activity : " . $drow->total_activity . ")" ,
					'status_date' => DB_global::Global_CurrentDatetime(),
					'user_modified' => $userData->id,
					'date_modified' => DB_global::Global_CurrentDatetime(),
                    'platform_id' => $platform_id,
				);	
				DB_global::cz_insert('awb_trn_point_history', $arrayHistory);
			}

            DB::table('awb_trn_article_import')
                ->where('id', '=', $trn_article_import_id)
                ->update([
                    'total_record' => $totalData,
				    'total_valid' => $totalValid
                ]);
			// clear current temp import
			DB_global::cz_execute_query('DELETE FROM awb_trn_article_import_detail_temp WHERE trn_article_import_id = ?', [$trn_article_import_id]);
			
            return response()->json([
                'data' => $totalData,
                'message' => 'success'
            ]);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function ImportFilteredData($trn_article_import_id, $platform_id)
    {
        try {
            $sql = "SELECT c.id     AS user_employee_id,
                        a.trn_article_import_id,
                        a.article_action,
                        a.article_id,
                        a.user_employee_name,
                        a.user_employee_email,
                        a.user_employee_function,
                        Max(a.content_datetime) AS content_datetime,
                        Max(a.article_comment)  AS article_comment,
                        Count(a.id)             AS total_redudance
                FROM   awb_trn_article_import_detail_temp a
                LEFT JOIN awb_trn_article_import_detail b
                    ON a.article_id = b.article_id
                        AND a.article_action = b.article_action
                        AND a.user_employee_id = b.user_employee_id
                        AND a.platform_id = b.platform_id
                LEFT JOIN users c
                    ON CONVERT(a.user_employee_id, signed INTEGER) =
                        CONVERT(c.id, signed INTEGER)
                LEFT JOIN awb_trn_article d
                    ON d.article_id = a.article_id
                        AND d.platform_id = a.platform_id
                WHERE  b.article_id IS NULL
                        AND a.article_action IN ( 'VIEW', 'COMMENT', 'LIKE', 'SHARE' )
                        AND c.id IS NOT NULL
                        AND d.id IS NOT NULL
                        AND a.trn_article_import_id = ?
                        AND a.platform_id = ?
                GROUP  BY c.id,
                        a.trn_article_import_id,
                        a.article_action,
                        a.article_id,
                        a.user_employee_name,
                        a.user_employee_email,
                        a.user_employee_function";
            return DB_global::cz_result_set($sql, [$trn_article_import_id, $platform_id]);
        } catch (\Throwable $th){
            echo $th;
            exit();
        }
    }

    function GenerateUserPoint($trn_article_import_id, $platform_id)
    {
        try {
            $sql = "SELECT user_employee_id, count(id) as total_activity, article_action 
                FROM awb_trn_article_import_detail 
                WHERE trn_article_import_id = ?
                AND platform_id = ?
                GROUP BY article_action, user_employee_id";
            return DB_global::cz_result_set($sql, [$trn_article_import_id, $platform_id]);
        } catch (\Throwable $th){
            echo $th;
            exit();
        }
    }

    public function ExportData($limit, $offset, $id="",$category="",$export=false)
    {	
        $sql = "select * from awb_trn_article_import_detail where md5(trn_article_import_id) = '$id'";
        $offset = ((isset($offset) && $offset <> "") ? $offset : 0);	
        if ($category != "COUNT" && $export == false)
        {
            $sql = $sql . " LIMIT  $offset,$limit ";
        }
        return $this->cz_result_set($sql,$category);
    }

    function SelectData($_md5ID)
    {
        $sql = "select * from " . $this->table_name ." where md5(id) = '$_md5ID' limit 1";
        #print $sql;
        return $this->cz_result_array($sql);
    }

    function DeleteData($id)
    {
        $this->cz_delete($this->table_name,'md5(id)',$id);

    }
}