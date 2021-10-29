<?php

//namespace App\Helpers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DB_global{

    public static function cz_execute_query($sql,$param = array(),$get_return_id = FALSE)
	{
        DB::enableQueryLog();

        $query = DB::statement($sql,$param);

        return DB::getQueryLog();
    }

    public static function cz_insert($table,$array_values,$get_return_id = FALSE)
    {
        DB::enableQueryLog();

        if($get_return_id == TRUE){
            $id = DB::table($table)->insertGetId(
               $array_values
            );
            return $id;
        }else{
            DB::table($table)->insert(
                $array_values
            );
            return DB::getQueryLog();
        }

    }

    public static function cz_count($my_table)
	{
		// Produces an integer, like 25
		return DB::table($my_table)->count();
    }

    public static function cz_select($query,$param = array(),$field_select = '',$print_query = false)
	{
        DB::enableQueryLog();

		$dataset = DB::select($query, $param);
		$value = '';
		if (count($dataset) > 0)
		{
				$data = (array) $dataset[0];
				$value = $data[$field_select];
        }

        if($print_query==TRUE){
            return array('data'=>$value, 'print'=> DB::getQueryLog());
        }else{
            return $value;
        }

    }

    public static function cz_update($table,$where_field,$where_update_value,$array)
	{
        DB::enableQueryLog();

        DB::table($table)
              ->where($where_field, $where_update_value)
              ->update($array);
        //---get query
        return DB::getQueryLog();
    }

    public static function cz_update_where_array($table,$array_data,$array_where)
	{
        DB::enableQueryLog();

        // example array_where -> ['status', '=', '1'],['subscribed', '<>', '1']
        DB::table($table)
        ->where($array_where)
        ->update($array_data);
		//---get query
		return DB::getQueryLog();
    }

    public static function cz_delete($table,$where_field_delete,$where_delete_value)
	{
        DB::enableQueryLog();
		//---clear data first
        DB::table($table)->where($where_field_delete,$where_delete_value)->delete();
		//---get query
		return DB::getQueryLog();
    }

    public static function cz_delete_array($table,$array_delete)
	{
        DB::enableQueryLog();
        //---delete array method
        // example array_where -> ['status', '=', '1'],['subscribed', '<>', '1']
        DB::table($table)->where($array_delete)->delete();
		//---get query
		return DB::getQueryLog();
	}

    public static function cz_result_array($query,$param=array(),$print_query = false,$category="")
	{
        DB::enableQueryLog();

		$dataset = DB::select($query, $param);
        $print = DB::getQueryLog();

		if ($category == "COUNT")
		{
			return ($print_query==TRUE ? array('data'=>count($dataset),'print'=>$print): count($dataset));
		}else{
            if(isset($dataset[0])){
                return ($print_query==TRUE ? array('data'=>(array) $dataset[0],'print'=>$print): (array) $dataset[0]);
            }
                return ($print_query==TRUE ? array('data'=>(array) [],'print'=>$print): (array) []);

		}

	}

	public static function cz_result_set($query,$param=array(),$print_query = false,$category="")
	{
        DB::enableQueryLog();

		$dataset = DB::select($query, $param);
		$print = DB::getQueryLog();
		if ($category == "COUNT")
		{
			return ($print_query==TRUE ? array('data'=>count($dataset),'print'=>$print): count($dataset));
		}else{
			return ($print_query==TRUE ? array('data'=>$dataset,'print'=>$print): $dataset);
		}

    }

    public static function bool_ValidateDataOnTableById_md5($_tableName,$_fieldId)
	{
		$dataset = DB::table($_tableName)->whereRaw('md5(id) = ?', [$_fieldId])->get();
        return $dataset;
    }

    public static function bool_CheckRowExist($sql,$param=array())
	{
		$dataset = DB::select($sql, $param);
		if (count($dataset) > 0)
		{
				return true;
		}
		return false;
    }

    public function GenerateLog($userName,$userId,$userAccount,$userEmail,$isMobileAccess,$module,$feature='')
	{
        /* $module = platform name */
		$array_data = array(
								'user_name'=> $userName,
								'user_id'=> $userId,
								'user_account'=> $userAccount,
								'user_email'=> $userEmail,
								'access_module'=>$module,
								'access_feature'=>$feature,
								'access_device'=>($isMobileAccess ? 'Mobile' : 'Desktop'),
								'access_date'=>$this->Global_CurrentDatetime()
							);
		$this->cz_insert('activity_log',$array_data);
    }

    public static function Global_CurrentDatetime()
	{
		return date('Y-m-d H:i:s');
    }

    public static function Global_CurrentDate()
	{
		return date('Y-m-d');
    }

    public function lang()
    {
        $sql = "select distinct country
				from users
				where country is not null
                order by country";
        return $this->cz_result_set($sql);
    }

    public function function()
    {
        $sql = "select distinct directorate
				from users
                where directorate is not null
                and directorate <> ' '
                order by directorate";
        return $this->cz_result_set($sql);
    }

    public function mstcontent($lang)
    {
        $param = [$lang];
        $sql = "select * from recognize_mst_content_lang where lang = ? and (is_deleted = 0 or is_deleted is null)
		order by lang limit 1";
        return $this->cz_result_array($sql,$param);
    }

    public static function cz_getTableColumns($table){
        return DB::getSchemaBuilder()->getColumnListing($table);
    }

    public static function IsUniqueVoteAndValidateData($postId,$userId,$behaviorId,$userCreated,$platform_id)
 	{
 		$boolResult = false;
         //---validate behavior is exist on master
        $sql = "select * from behavior where status_active = 1 and id = ? and platform_id = ?";
        $param = [$behaviorId,$platform_id];
        $boolean = self::bool_CheckRowExist($sql, $param);
 		if($boolean)
 		{
 			$boolResult = self::ValidateUserVoteExist($postId,$userId,$behaviorId,$userCreated);
	 	}
 		return $boolResult;
    }

    static function ValidateUserVoteExist($postId,$userId,$behaviorId,$userCreated)
	{
		$sql = "select * from user_vote
				where user_post_id = ? and user_id = ?
					and behavior_id = ?
					and user_created = ? ";

		if(!self::bool_CheckRowExist($sql, [$postId,$userId,$behaviorId,$userCreated]))
		{
			return true;
		}
		return false;
    }
    public static function GetBehaviorId($hashtag)
	{
        $sql = 'select * from behavior where hashtag = ? or hashtag_ind = ?';
		return self::cz_select($sql, [$hashtag,$hashtag], 'id');
    }
    public static function UpdatePostContent($post_content,$id)
	{
		$array_header = array(
						'post_content'=>$post_content);
        self::cz_update('user_post','id',$id,$array_header);
    }
    public static function RsEmailNotificationByPostId($postId,$platform_id)
	{
        $sql = "SELECT
                        d.email as receiver_email,
                        d.name as receiver_name,
                        c.name as sender_name,
                        c.email as sender_email,
                        e.email as spv_email,
                        sum(b.point_score) as total
                    FROM user_post a
                    LEFT JOIN user_vote b on a.id = b.user_post_id
                    LEFT JOIN users c on a.user_created  = c.id
                    LEFT JOIN users d on b.user_id = d.id
                    LEFT JOIN users e on d.supervisor_id = e.id
                    WHERE
                        a.id = ? and
                        a.platform_id = ?
                GROUP BY a.id, d.name, d.email, e.email";
		return self::cz_result_set($sql,[$postId,$platform_id]);
    }
    public static function InsertEmailLog($array_data)
	{
		return self::cz_insert('email_log',$array_data,TRUE);
    }

    public static function cleanFileName($originalName)
    {
        $originalName = preg_replace('/[^a-zA-Z0-9 -()._]+/', '', $originalName);
        $cleanName = str_replace(' ', '_', $originalName);
        return $cleanName;
    }

    public static function is_number($var)
    {
        if ($var == (string) (float) $var) {
            return (bool) is_numeric($var);
        }
        if ($var >= 0 && is_string($var) && !is_float($var)) {
            return (bool) ctype_digit($var);
        }
        return (bool) is_numeric($var);


    /*
    is_number(12); // true
    is_number(-12); // true
    is_number(-12.2); // true
    is_number("12"); // true
    is_number("-124.3"); // true
    is_number(0.8); // true
    is_number("0.8"); // true
    is_number(0); // true
    is_number("0"); // true
    is_number(NULL); // false
    is_number(true); // false
    is_number(false); // false
    is_number("324jdas32"); // false
    is_number("123-"); // false
    is_number(1e7); // true
    is_number("1e7"); // true
    is_number(0x155); // true
    is_number("0x155"); // false
    */

    }

    public static function Global_ConvDateIndToEng($indonesia_format = '')
	{

		$value = '';
		if ($indonesia_format != '')
		{
			$temp_english_format = explode("-",$indonesia_format);
			$tanggal = $temp_english_format[0];
			$bulan = $temp_english_format[1];
			$tahun = $temp_english_format[2];

			$value = $tahun . "/" . $bulan . "/" . $tanggal;

		}
		return $value;
	}

    public static function StringReadMore($story_desc, $chars = 28)
    {
        if(strlen($story_desc) > $chars)
        {
			$story_desc = substr($story_desc,0,$chars);
			$story_desc = substr($story_desc,0,strrpos($story_desc,' '));
			$story_desc = $story_desc . " ...";
        }
        return $story_desc;
	}

    public static function GlobalWordWrap($string, $width = 75, $break = "\n")
    {
        // split on problem words over the line length
        $pattern = sprintf('/([^ ]{%d,})/', $width);
        $output = '';
        $words = preg_split($pattern, $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        foreach ($words as $word) {
            if (false !== strpos($word, ' ')) {
                // normal behaviour, rebuild the string
                $output .= $word;
            } else {
                // work out how many characters would be on the current line
                $wrapped = explode($break, wordwrap($output, $width, $break));
                $count = $width - (strlen(end($wrapped)) % $width);

                // fill the current line and add a break
                $output .= substr($word, 0, $count) . $break;

                // wrap any remaining characters from the problem word
                $output .= wordwrap(substr($word, $count), $width, $break, true);
            }
        }

        // wrap the final output
        return wordwrap($output, $width, $break);
	}

    public static function GlobalLoadAHref($strUrl) {
		if (false === strpos($strUrl, 'http'))
		{
			if(strlen($strUrl) > 0)
			{
				$strUrl = 'http://' . str_replace('//','',$strUrl);
			}
			else
			{
				$strUrl = '#';
			}
		}
		// wrap the final output
		return $strUrl;
	}
}
