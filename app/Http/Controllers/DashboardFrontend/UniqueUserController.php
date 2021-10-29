<?php

namespace App\Http\Controllers\DashboardFrontend;


use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


class UniqueUserController extends Controller
{
  
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ListData(Request $request)
    {

        if($request->input('startDate')!='' && $request->input('endDate') != ''){
            $from           =   $request->input('startDate');
            $to             =   $request->input('endDate');
                
        }
        else{
            $from           =   mktime(0,0,0,date("n"),date("j")-20,date("Y"));
            $from           =   date("Y-m-d", $from);
            $to             =   date("Y-m-d");            
        }
        $fromReturn =   $from; 
        $toReturn   =   $to;

        $whereApp       =   "";
        if($request->input('app_name') != 'All'){
            $whereApp   =   "and activity_log.access_module like '".$request->input('app_name')."%'     ";
        }


        $sqlMobile     =   "
        select 
            count(DISTINCT(activity_log.user_id)) as total 
        from 
            activity_log,
            users,
            menu_users_info
        WHERE
            users.id = activity_log.user_id
            and users.id = menu_users_info.id
            and activity_log.access_device = 'Mobile'
            $whereApp
            and date(activity_log.access_date)  BETWEEN '". $from ."' AND '".$to."'
        ";
        $dataCountMobile  = DB_global::cz_result_array($sqlMobile,[]);

        $sqlDesktop     =   "
        select 
        count(DISTINCT(activity_log.user_id)) as total 
        from 
            activity_log,
            users,
            menu_users_info
        WHERE
            users.id = activity_log.user_id
            and users.id = menu_users_info.id
            and activity_log.access_device = 'Desktop'
            $whereApp
            and date(activity_log.access_date) BETWEEN '". $from ."' AND '".$to."'
        ";
        $dataCountDesktop   = DB_global::cz_result_array($sqlDesktop,[]);

        $total              =   $dataCountMobile['total'] + $dataCountDesktop['total'];


        if($total > 0){
            $desktopPercent     =    (100 * $dataCountDesktop['total']) / $total;
            $mobilePercent      =    (100 * $dataCountMobile['total'])  / $total;
        }
        else{
            $desktopPercent     =    0;
            $mobilePercent      =    0;
        }
        

        $dataSet[] = array(
            'data'              => array($desktopPercent,$mobilePercent),
            'backgroundColor'   => "#FDD835",
            'label'             =>  "Percentage (%)"
        );

        $data = array(
            'labels'        => array( "Desktop", "Mobile"),
            'datasets'      => $dataSet
        );

        $dataNumber = array(
            array(
                
                'name'  =>  'All',
                'total' =>   $total
            ),
            array(
                'name'  =>  'Desktop',
                'total' =>  $dataCountDesktop['total']
            ),
            array(
                'name'  =>  'Mobile',
                'total' =>  $dataCountMobile['total']    
            )
            
        );

        try {
            #print $sql;
            //$data = DB_global::cz_result_set($sql,$param,false,$category);

            return response()->json([
                'data'          => $data,
                'dataNUmber'    => $dataNumber,
                'fromReturn'    => $fromReturn,
                'toReturn'      => $toReturn,
                'message'       => 'success'
            ]);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
       
    }


   
}
