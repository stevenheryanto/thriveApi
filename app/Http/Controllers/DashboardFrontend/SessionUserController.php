<?php

namespace App\Http\Controllers\DashboardFrontend;


use DB_global;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


class SessionUserController extends Controller
{
  
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ListData(Request $request)
    {

       
        $appArrays      =   array(
            array(
                'name' => 'Landing Page',
                'color' => '#fd7e14'
            ),
            array(
                'name' => 'Recognition',
                'color' => '#ffc107'
            ),
            array(
                'name' => 'Time to Think',
                'color' => '#28a745'
            )
        );  

        if($request->input('app_name') != 'All'){
            foreach($appArrays as $appArray  ){
                if ($appArray['name'] == 'Recognition') {
                    $colorApp   =     $appArray['color'];
                }
            }

            $appArrays      =   array(
                array(
                    'name'  => $request->input('app_name'),
                    'color' => $colorApp
                )
            );

        }

        $labelsArray    =   array();


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
               
        
        while (strtotime($from) <= strtotime($to)){

            $fromLabel   =   date("d-M", strtotime($from));
        
            array_push($labelsArray,$fromLabel);

            foreach($appArrays as $appArray){
                $sql        =   "select count(id) as total from activity_log where access_module like '".$appArray['name']."%' and  DATE(access_date) = '".$from."'";
                $dataCount  = DB_global::cz_result_array($sql,[]);
              
                $arrayTotal[$appArray['name']][] = $dataCount['total'];


            }

            $from   =   mktime(0,0,0,date("m",strtotime($from)),date("d",strtotime($from))+1,date("Y",strtotime($from)));
            $from   =   date("Y-m-d", $from);

            
        }

        

        foreach($appArrays as $appArray){
            $dataSet[] = array(
                'data' => $arrayTotal[$appArray['name']],
                'borderColor' => $appArray['color'],
                'label' => $appArray['name'],
                'fill' => false
            );
          
        }

        $data = array(
            'type'      => 'line',
            'title'     => 'Title',
            'labels'    => $labelsArray,
            'datasets'  =>  $dataSet
            
        );

       try {
            #print $sql;
            //$data = DB_global::cz_result_set($sql,$param,false,$category);

            return response()->json([
                'data'          => $data,
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
