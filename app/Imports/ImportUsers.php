<?php

namespace App\Imports;


use DB_global;

//use App\User;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportUsers implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        
        if($row[0] != 'IMDL ID'){
            //echo $row[0];

            $id                         =   str_replace(array("\r", "\r\n", "\n"), '', $row[0]);
            $group_grade                =   str_replace(array("\r", "\r\n", "\n"), '', $row[1]);
            $group_function             =   str_replace(array("\r", "\r\n", "\n"), '', $row[2]);
            $group_basetown_location    =   str_replace(array("\r", "\r\n", "\n"), '', $row[3]);
            $generation                 =   str_replace(array("\r", "\r\n", "\n"), '', $row[4]);

            $sql = "
            select
                *
            from
                menu_users_info
            where 
                id ='".$id."'
            ";
            $cekReady     =   DB_global::cz_result_set($sql,[]);

            if(!$cekReady){
              // echo  
               $sql = "
                insert into
                    menu_users_info
                        (id,group_grade,group_function,group_basetown_location,generation,date_created)
                    values
                        ('".$id."','".$group_grade."','".$group_function."','".$group_basetown_location."','".$generation."','". DB_global::Global_CurrentDatetime()."')
                ";
                $insert     =   DB_global::cz_result_set($sql,[]);
            }
            else{
               //echo 
               $sql = "
                update 
                    menu_users_info
                set
                    group_grade             = '".$group_grade."',
                    group_function          = '".$group_function."',
                    group_basetown_location = '".$group_basetown_location."',
                    generation              = '".$generation."',
                    date_created          = '".DB_global::Global_CurrentDatetime()."'
                where
                    id = '".$id."'

                ";
                $update     =   DB_global::cz_result_set($sql,[]);
            }
        }
        
    }


}