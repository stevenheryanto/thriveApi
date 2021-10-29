<?php

namespace App\Http\Controllers\DashboardFrontend;


use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


class TTRByBehaviorController extends Controller
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


        $sqlGrandTotal = "
            select 
                ifnull(sum(c.point_score),0) as grand_total
            from 
                signature a 
                left join behavior b on a.id = b.signature 
                left join user_vote c ON b.id = c.behavior_id
            where 
                (a.status_active = 1 and a.is_deleted = 0) 
                and  (b.status_active = 1 and b.is_deleted = 0)
                and convert(c.date_created,date) BETWEEN '$from' and '$to'
                
        ";
        $dataGrandTotal     =   DB_global::cz_result_array($sqlGrandTotal,[]);
        //var_dump($dataGrandTotal['grand_total']);exit();
        $grandTotal         =    $dataGrandTotal['grand_total'];
		$sql = "select  name,round((total /  $grandTotal) * 100) as y  from 
			(
				select b.behavior as name, ifnull(sum(c.point_score),0) as total
					from signature a left join
						 behavior b on a.id = b.signature left join
						 user_vote c ON b.id = c.behavior_id
					where 
						(a.status_active = 1 and a.is_deleted = 0) and 
						(b.status_active = 1 and b.is_deleted = 0)
						and convert(c.date_created,date) BETWEEN '$from' and '$to'
					group by b.behavior
			) as vw
		";
        $dataBehaviors     =   DB_global::cz_result_set($sql,[]);
        //var_dump($dataBehaviors);exit();
        $totalAllData = 0;
        foreach($dataBehaviors as $dataBehavior){
            $totalAllData   +=  $dataBehavior->y;
        }
        //echo $totalAllData;
        //exit();

        $labelsArray            =   array();
        $dataInDataSets         =   array();
        $dataNUmber             =   array();
        foreach($dataBehaviors as $dataBehavior){
            array_push($labelsArray,$dataBehavior->name."(%)");

            if($dataBehavior->y > 0){
                $dataPercent      =   (100 * $dataBehavior->y) / $totalAllData;
                $dataPercent      =   round($dataPercent,2);
            }
            else{
                $dataPercent      =   0;
            }
            array_push($dataInDataSets,$dataPercent);
          
           
            
            array_push($dataNUmber,
                array(
                    'name'  => $dataBehavior->name,
                    'total' => $dataBehavior->y
                )
            );


        }


        $dataSet[] = array(
            'data'              => $dataInDataSets,
            'label'             =>  "Percentage (%)"
            
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
