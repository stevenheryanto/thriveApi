<?php

namespace App\Http\Controllers\DashboardFrontend;


use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


class TTRBySignatureController extends Controller
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




        $sql                    =   "
        select 
            concat(name) as name,
            total as y  
        from 
		        (
                    SELECT 
					    a.signature AS name, IFNULL(sum(c.point_score), 0) AS total
				    FROM
					    signature a
				        LEFT JOIN behavior b ON a.id = b.signature
				        LEFT JOIN user_vote c ON b.id = c.behavior_id
				    WHERE
					    (a.status_active = 1 AND a.is_deleted = 0)
						AND (b.status_active = 1 AND b.is_deleted = 0)
						AND CONVERT( c.date_created , DATE) BETWEEN  '$from' and '$to'
                    GROUP BY 
                        a.signature
                ) 
                as xrz_vw
        ";
        $dataSignatures     =   DB_global::cz_result_set($sql,[]);
       
        $totalAllData = 0;
        foreach($dataSignatures as $dataSignature){
            $totalAllData   +=  $dataSignature->y;
        }
        //echo $totalAllData;
        //exit();

        $labelsArray            =   array();
        $dataInDataSets         =   array();
        $dataNUmber             =   array();
        foreach($dataSignatures as $dataSignature){
            array_push($labelsArray,$dataSignature->name."(%)");

            if($dataSignature->y > 0){
                $dataPercent      =   (100 * $dataSignature->y) / $totalAllData;
                $dataPercent      =   round($dataPercent,2);
            }
            else{
                $dataPercent      =   0;
            }
            array_push($dataInDataSets,$dataPercent);
          
           
            
            array_push($dataNUmber,
                array(
                    'name'  => $dataSignature->name,
                    'total' => $dataSignature->y
                )
            );


        }


        $dataSet[] = array(
            'data'              => $dataInDataSets,
            'label'             =>  "Percentage (%)",
            'backgroundColor'   =>  array('#ffcb03', '#d20f7c', '#ef7e04', '#670e37', '#6610f2',
            '#17a2b8', '#e27fb8', '#cc1ca0', '#ff8383')
            
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
