<?php

namespace App\Http\Controllers\DashboardFrontend;


use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


class GenerationUserController extends Controller
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
        

        $sql                    =   "
            select 
                DISTINCT(generation) as generation 
            from 
                menu_users_info
            order by
                generation
        ";
        $dataGroupBasetownLocations     =   DB_global::cz_result_set($sql,[]);

        $totalAllData = 0;
        foreach($dataGroupBasetownLocations as $dataGroupBasetownLocation){
            $sql                    =   "
            select 
                count(DISTINCT(activity_log.user_id)) as total 
            from 
                activity_log,
                users,
                menu_users_info
            where
                activity_log.user_id = users.id
                and users.id = menu_users_info.id
                $whereApp
                and date(activity_log.access_date) BETWEEN '". $from ."' AND '".$to."'
                and menu_users_info.generation = '".$dataGroupBasetownLocation->generation."'
            ";
            $dataTotal      =   DB_global::cz_result_array($sql,[]);
           // echo $dataTotal['total'];

            $totalAllData   +=  $dataTotal['total'];
        }
        //echo $totalAllData;
        //exit();

        $labelsArray            =   array();
        $dataInDataSets         =   array();
        $dataNUmber             =   array();
        foreach($dataGroupBasetownLocations as $dataGroupBasetownLocation){
            array_push($labelsArray,$dataGroupBasetownLocation->generation);


            $sql                    =   "
            select 
                count(DISTINCT(activity_log.user_id)) as total 
            from 
                activity_log,
                users,
                menu_users_info
            where
                activity_log.user_id = users.id
                and users.id = menu_users_info.id
                $whereApp
                and date(activity_log.access_date) BETWEEN '". $from ."' AND '".$to."') 
                and menu_users_info.generation = '".$dataGroupBasetownLocation->generation."'
            ";
            $dataTotal      =   DB_global::cz_result_array($sql,[]);


            if($dataTotal['total'] > 0){
                $dataPercent      =   (100 * $dataTotal['total']) / $totalAllData;
            }
            else{
                $dataPercent      =   0;
            }
            array_push($dataInDataSets,$dataPercent);
          
           
            
            array_push($dataNUmber,
                array(
                    'name'  => $dataGroupBasetownLocation->generation,
                    'total' => $dataTotal['total']
                )
            );


        }


        $dataSet[] = array(
            'data'              => $dataInDataSets,
            'label'             =>  "Percentage (%)",
            'backgroundColor'   =>  "#FDD835"
            
        );

        $data = array(
            'labels'        => $labelsArray,
            'datasets'      => $dataSet
        );

        try {            
            return response()->json([
                'data' => $data,
                'dataNUmber' => $dataNUmber, 
                'fromReturn'    => $fromReturn,
                'toReturn'      => $toReturn,
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


   
}
