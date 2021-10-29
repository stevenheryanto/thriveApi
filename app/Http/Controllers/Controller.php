<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Nullix\CryptoJsAes\CryptoJsAes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function GetMd5(Request $request)
    {
        $md5id = md5($request->input('id'));
        try {
            return response()->json([
                'data' => $md5id,
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

    // public function GetCredential(Request $request)
    // {
    //     // $originalValue = [env('AZURE_CLIENT_ID'), env('AZURE_TENANT_ID')];
    //     $originalValue = env('AZURE_CLIENT_ID');
    //     $password = "thrive123";
    //     $encrypted = CryptoJsAes::encrypt($originalValue, $password);
    //     try {
    //         return response()->json([
    //             'data' => $encrypted,
    //             'dataoriginal'=>$originalValue,
    //             'message' => 'success'
    //         ]);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'data' => false,
    //             'message' => 'failed: '.$th
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function MoveFile(Request $request)
    {
        try {
            // Storage::disk('local')->makeDirectory('huhuh');
            // $files = Storage::disk('local')->files('article');
            // print_r($files);
            $totalMove = 0;
            // $listCourse = DB::table('awb_mst_course')->select('id','home_image','course_image')->get();
            // foreach($listCourse as $course){
            //     echo $course->id. ": home:-". $course->home_image. "-course: ". $course->course_image .PHP_EOL;
            //     if(isset($course->home_image) && $course->home_image <> ' '){
            //         $ext = pathinfo($course->home_image, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg'){
            //             Storage::disk('local')->move('article/'. $course->home_image, 'course/'. $course->home_image);
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'home kosong'.PHP_EOL;
            //     }
            //     if(isset($course->course_image) && $course->course_image <> ' '){
            //         $ext = pathinfo($course->course_image, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg'){
            //             Storage::disk('local')->move('article/'. $course->course_image, 'course/'. $course->course_image);                    
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'course kosong'.PHP_EOL;
            //     }
            // }
            // $listWorkshop = DB::table('awb_trn_workshop_sharing')->select('id','workshop_image','workshop_preview_image')->get();
            // foreach($listWorkshop as $workshop){
            //     echo $workshop->id. ": image:-". $workshop->workshop_image. "-preview: ". $workshop->workshop_preview_image .PHP_EOL;
            //     if(isset($workshop->workshop_image) && $workshop->workshop_image <> ' '){
            //         $ext = pathinfo($workshop->workshop_image, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg'){
            //             Storage::disk('local')->move('article/'. $workshop->workshop_image, 'workshop/'. $workshop->workshop_image);
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'image kosong'.PHP_EOL;
            //     }
            //     if(isset($workshop->workshop_preview_image) && $workshop->workshop_preview_image <> ' '){
            //         $ext = pathinfo($workshop->workshop_preview_image, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg'){
            //             Storage::disk('local')->move('article/'. $workshop->workshop_preview_image, 'workshop/'. $workshop->workshop_preview_image);                    
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'preview kosong'.PHP_EOL;
            //     }
            // }

            // $listSlider = DB::table('awb_mst_slider')->select('id','slider_video','slider_video_mobile')->get();
            // foreach($listSlider as $slider){
            //     echo $slider->id. ": image:-". $slider->slider_video. "-preview: ". $slider->slider_video_mobile .PHP_EOL;
            //     if(isset($slider->slider_video) && $slider->slider_video <> ' '){
            //         $ext = pathinfo($slider->slider_video, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg' || $ext == 'gif'){
            //             Storage::disk('local')->move('daily_feeds/'. $slider->slider_video, 'slider/'. $slider->slider_video);
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'image kosong'.PHP_EOL;
            //     }
            //     if(isset($slider->slider_video_mobile) && $slider->slider_video_mobile <> ' '){
            //         $ext = pathinfo($slider->slider_video_mobile, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg' || $ext == 'gif'){
            //             Storage::disk('local')->move('daily_feeds/'. $slider->slider_video_mobile, 'slider/'. $slider->slider_video_mobile);                    
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'preview kosong'.PHP_EOL;
            //     }
            // }

            // $listSlider = DB::table('awb_mst_slider_category')->select('id','slider_video','slider_video_mobile')->get();
            // foreach($listSlider as $slider){
            //     echo $slider->id. ": image:-". $slider->slider_video. "-preview: ". $slider->slider_video_mobile .PHP_EOL;
            //     if(isset($slider->slider_video) && $slider->slider_video <> ' '){
            //         $ext = pathinfo($slider->slider_video, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg' || $ext == 'gif'){
            //             Storage::disk('local')->move('daily_feeds/'. $slider->slider_video, 'slider_category/'. $slider->slider_video);
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'image kosong'.PHP_EOL;
            //     }
            //     if(isset($slider->slider_video_mobile) && $slider->slider_video_mobile <> ' '){
            //         $ext = pathinfo($slider->slider_video_mobile, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg' || $ext == 'gif'){
            //             Storage::disk('local')->move('daily_feeds/'. $slider->slider_video_mobile, 'slider_category/'. $slider->slider_video_mobile);                    
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'preview kosong'.PHP_EOL;
            //     }
            // }
            
            // $listCategory = DB::table('awb_trn_category')->select('id','category_image')->get();
            // foreach($listCategory as $category){
            //     echo $category->id. ": image:-". $category->category_image.PHP_EOL;
            //     if(isset($category->category_image) && $category->category_image <> ' '){
            //         $ext = pathinfo($category->category_image, PATHINFO_EXTENSION);
            //         if($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg' || $ext == 'gif'){
            //             Storage::disk('local')->move('article/'. $category->category_image, 'category/'. $category->category_image);
            //             $totalMove++;
            //         }
            //     } else {
            //         echo 'image kosong'.PHP_EOL;
            //     }
            // }
            return response()->json([
                'data' => $totalMove,
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
