<?php

namespace App\Http\Controllers\AWB;

use DB_global;
use AwbGlobal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendEmailAwbShareArticle;
use App\Models\AWB\awb_trn_article;
use App\Models\AWB\awb_trn_article_log;
use App\Models\AWB\awb_trn_article_spesific_user;
use App\Models\AWB\awb_mst_menu;
use App\Models\AWB\awb_mst_config;
use App\Models\AWB\awb_mst_content_your_own_network;
use App\Models\AWB\awb_mst_faq;
use App\Models\AWB\awb_trn_article_share;
use App\Models\AWB\awb_mst_page;
use App\Models\AWB\awb_mst_section;
use App\Models\AWB\awb_mst_slider;
use App\Models\AWB\awb_mst_reg_period;
use App\Models\AWB\awb_trn_quiz_user;
use App\Models\AWB\awb_trn_category;
use App\Models\AWB\awb_trn_quiz;
use App\Models\AWB\awb_trn_workshop_sharing;
use App\Models\AWB\awb_trn_workshop_sharing_user;
use App\Models\AWB\awb_user_pref_topic;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class HomeController extends Controller
{
    public function MenuPageContent(Request $request)
    {
        /* MenuPageContentByCategoryId */
        $platform_id = $request->input('platform_id');
        $menu_id = $request->input('menu_id');
        $category_id = $request->input('category_id');
        $section_id = $request->input('section_id');

        $enable_iqos_delivery = awb_mst_config::where('_code', '=', 'enable_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
		$category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
        $iqos_banner_url = awb_mst_config::where('_code', '=', 'IQOS_BANNER_URL')->where('platform_id', '=', $platform_id)->value('value');
        try{
            $subRight = awb_trn_category::where(DB::raw('md5(id)'), '=', $category_id)
                ->where('flag_active', '=', 1)
                ->when($enable_iqos_delivery == 'FALSE', function($subRight) use($category_iqos_delivery){
                    $subRight->where('id', '<>', $category_iqos_delivery);
                });

            if(isset($section_id)){
                $query = awb_mst_section::where([[DB::raw('md5(id)'), '=', $section_id],['flag_active','=',1],['platform_id', '=', $platform_id]]);
            }else{
                $query = awb_mst_menu::when(isset($category_id), function($query) use($subRight){
                    $query->rightJoinSub($subRight, 't', function ($join){
                        $join->on('t.menu_id','=','awb_mst_menu.id');
                });
                })->when(isset($menu_id), function($query) use($menu_id){
                    $query->where(DB::raw('md5(awb_mst_menu.id)'), '=', $menu_id);
                })
                ->where('awb_mst_menu.platform_id', '=', $platform_id)
                ->where('awb_mst_menu.flag_active', '=', 1);
            }

            $data = $query->get();
            return response()->json([
                'data' => $data,
                'data2' => $enable_iqos_delivery,
                'data3' => md5($category_iqos_delivery),
                'data4' => $iqos_banner_url,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function StrWhereArticleRefineBy($category4)
	{
		$temp = explode(',', $category4);
		$comma_separated = implode("','", $temp);
		return "'".$comma_separated."'";
    }

    public function ListArticleByMenuId(Request $request)
	{
        //cara menggunakan function ini
        //function ini berguna untuk mencari list article by menu id / category id / article id / section id
        //input request yang wajib adalah platform_id, user_id, dan category4
        //untuk list article by menu id, input yang harus dikirim dari frontend adalah menu_id dan query akan masuk ke elsenya when(isset($article_id) lalu when(!isset($category_id) lalu when(isset($menu_id)
        //untuk list article by category id, input yang harus dikirim dari frontend adalah category_id dan query akan masuk ke elsenya when(isset($article_id)lalu when(isset($category_id)
        //untuk list article by article id, input yang harus dikirim dari frontend adalah article_id dan query akan masuk ke when(!isset($category_id) && !isset($menu_id) lalu elsenya when(isset($section_id) dan when(isset($article_id)
        //untuk list article by section id, input yang harus dikirim dari frontend adalah section_id dan query akan masuk ke when(!isset($category_id) && !isset($menu_id) lalu when(isset($section_id)
        //param filter search untuk mencari spesifik article by search
        //category4 adalah refine by yang berisi json string, jika tidak ingin mengirim category4 cukup buat array kosong dari frontend

        $platform_id = $request->input('platform_id');
        $menu_id = $request->input('menu_id');
        $category_id = $request->input('category_id');
        $user_id = $request->input('user_id');
        $sortBy = $request->input('sortBy');
        $filter_search = $request->input('filter_search');
        $category4 = $request->input('category4');
        $arrCategory4 = json_decode($category4);
        $article_id = $request->input("article_id");
        $section_id = $request->input('section_id');

		$enable_iqos_delivery = awb_mst_config::where('_code', '=', 'enable_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
		$category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');

        $subQuiz = awb_trn_quiz_user::distinct()->select('trn_article_id')
            ->where('user_modified', '=', $user_id)
            ->where('platform_id', '=', $platform_id);
        $subLog = awb_trn_article_log::select('trn_article_id', 'date_read')
            ->where('user_id', '=', $user_id)
            ->where('platform_id', '=', $platform_id);
        $subShare = awb_trn_article_spesific_user::select('trn_article_id')
            ->where('user_id', '=', $user_id);
        $query = awb_trn_article::select('awb_trn_article.*','b.menu_id as menuId',
                'b.title as category_title',
                DB::raw('case when awb_trn_article.flag_quiz =  1 and x.trn_article_id is null
                    then 1 else 0
                    end as show_quiz'),
                DB::raw('case when y.date_read is not null
                    then 1 else 0
                    end as flag_read')
            )->leftJoin('awb_trn_category as b', 'b.id', '=', 'awb_trn_article.category_id')
            ->leftJoinSub($subQuiz, 'x', function ($join){
                $join->on('x.trn_article_id', '=', 'awb_trn_article.id');
            })
            ->leftJoinSub($subLog, 'y', function ($join){
                $join->on('y.trn_article_id', '=', 'awb_trn_article.id');
            })
            ->when(!isset($category_id) && !isset($menu_id)   , function($join) use ($section_id, $filter_search){
                $join->leftJoin('awb_mst_menu as c','b.menu_id','=','c.id')
                ->when(isset($section_id), function($query) use($section_id){
                    $query->where('c.section_id','=',$section_id);
                },function($query) use($filter_search){
                    $query->when(isset($filter_search), function($query){
                        $query->whereIn('c.section_id',[2,3]);
                    });
                })
                ->where('b.flag_active','=',1);
            })
            ->where('awb_trn_article.flag_active', '=', 1)
            ->when(isset($article_id),function($query) use($article_id){
                $query->where(DB::raw('md5(awb_trn_article.id)'),'=',$article_id);
            }, function ($query) use($category_id, $menu_id, $user_id){
                $query->when(isset($category_id), function($query) use ($category_id){
                    $query->where('b.id','=',$category_id);
                })
                ->when(!isset($category_id), function($query) use ($menu_id, $user_id){
                    $query->when(isset($menu_id), function($query) use ($menu_id, $user_id){
                        $query->where('b.menu_id', '=', $menu_id)->where(function ($query) use($user_id){
                            $query->where('awb_trn_article.flag_show_spesific_user', '=', 0)
                                ->orWhereIn('awb_trn_article.id', function($query) use($user_id){
                                    $query->select(DB::raw(1))
                                        ->from('awb_trn_article_spesific_user')
                                        ->where('awb_trn_article_spesific_user.user_id', '=', $user_id);
                                });
                        });
                    });
                });
            })
            ->where('awb_trn_article.platform_id', '=', $platform_id)
            ->when(isset($filter_search), function($query) use($filter_search){
                $query->where('awb_trn_article.title', 'like', '%'.$filter_search.'%')
                ->orWhere('awb_trn_article.description', 'like', '%'.$filter_search.'%')
                ->orWhere('awb_trn_article.tags', 'like', '%'.str_replace('#','',$filter_search).'%');
            })
            ->when($enable_iqos_delivery == 'FALSE', function($query) use($category_iqos_delivery){
                $query->where('b.id', '<>', $category_iqos_delivery);
            })
            ->when(count($arrCategory4) > 0, function($query) use($arrCategory4){
                $query->whereIn('awb_trn_article.category_4', $arrCategory4);
            })
            ->when($sortBy == 0, function($query){
                $query->orderBy('awb_trn_article.id', 'ASC');
            })
            ->when($sortBy == 1, function($query){
                $query->orderBy('awb_trn_article.id', 'DESC');
            })
            ->when($sortBy == 2, function($query){
                $query->orderBy('b.title', 'ASC');
            })
            ->when($sortBy == 3, function($query){
                $query->orderBy('b.title', 'DESC');
            });
            /* dari tempat lama (sortBy = 4 /Most Popular) memang tidak ada orderBy apapun */
            //$data = $query->toSql();
            //print $data;
            //exit();
        try {
            $data = $query->get();
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

    public function LayoutPageList(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $query = awb_mst_page::where('platform_id', '=', $platform_id);
        try {
            $data = $query->get();
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

	public function LayoutSectionList(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $query = awb_mst_section::where('platform_id', '=', $platform_id)
            ->where('flag_active', '=', 1)
            ->where('navbar_active','=',1)
            ->orderBy('sort_index')
            ->orderBy('title');
        try {
            $data = $query->get();
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

	public function LayoutMenuList(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $query = awb_mst_menu::select('*',DB::raw('md5(id) as pageId'))->where('platform_id', '=', $platform_id)
            ->where('flag_active', '=', 1)
            ->orderBy('sort_index')
            ->orderBy('title');
        try {
            $data = $query->get();
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

    public function LayoutCategoryList(Request $request)
    {
        $platform_id = $request->input('platform_id');
		$enable_iqos_delivery = awb_mst_config::where('_code', '=', 'enable_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
		$category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');

        $query = awb_mst_menu::select('awb_mst_menu.id as menu_id',
            'awb_mst_menu.title as menu_title',
            'awb_mst_menu.section_id',
            'b.title as category_title',
            'b.id as category_id',
            DB::raw('md5(b.id) as pageId')
            )->leftJoin('awb_trn_category as b', 'b.menu_id', '=', 'awb_mst_menu.id')
            ->where('awb_mst_menu.flag_active', '=', 1)
            ->where('b.flag_active', '=', 1)
            ->where('awb_mst_menu.platform_id', '=', $platform_id)
            ->when($enable_iqos_delivery == 'FALSE', function($query) use($category_iqos_delivery){
                $query->where('b.id', '<>', $category_iqos_delivery);
            })
            ->orderBy('awb_mst_menu.title')
            ->orderBy('b.title')
            ;
        try {
            $data = $query->get();
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

    public function LayoutComboList(Request $request)
    {
        $platform_id = $request->input('platform_id');
		$enable_iqos_delivery = awb_mst_config::where('_code', '=', 'enable_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
		$category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');

        try {
            $dataSection = awb_mst_section::where('platform_id', '=', $platform_id)
                ->where('flag_active', '=', 1)
                ->where('navbar_active','=',1)
                ->orderBy('sort_index')
                ->orderBy('title')
                ->get();
            $dataMenu = awb_mst_menu::select('*',DB::raw('md5(id) as pageId'))
                ->where('platform_id', '=', $platform_id)
                ->where('flag_active', '=', 1)
                ->orderBy('sort_index')
                ->orderBy('title')
                ->get();
            $dataCategory = awb_mst_menu::select('awb_mst_menu.id as menu_id',
                'awb_mst_menu.title as menu_title',
                'awb_mst_menu.section_id',
                'b.title as category_title',
                'b.id as category_id',
                DB::raw('md5(b.id) as pageId')
                )->leftJoin('awb_trn_category as b', 'b.menu_id', '=', 'awb_mst_menu.id')
                ->where('awb_mst_menu.flag_active', '=', 1)
                ->where('b.flag_active', '=', 1)
                ->where('awb_mst_menu.platform_id', '=', $platform_id)
                ->when($enable_iqos_delivery == 'FALSE', function($query) use($category_iqos_delivery){
                    $query->where('b.id', '<>', $category_iqos_delivery);
                })
                ->orderBy('awb_mst_menu.title')
                ->orderBy('b.title')
                ->get();

            return response()->json([
                'data1' => $dataSection,
                'data2' => $dataMenu,
                'data3' => $dataCategory,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function rsSidebarMenuSearchList(Request $request){
        $platform_id = $request->input('platform_id');

        $query = awb_mst_section::select(DB::raw('md5(awb_mst_section.id) as md5id'),
                DB::raw("case when awb_mst_section.id = 6 then 'Content for You' else awb_mst_section.title end as title"),
                DB::raw('case when b.id is not null then 1 else 0 end show_submenu'),
                DB::raw("'section' as controller")
            )->leftJoin('awb_mst_menu as b', function($join){
                $join->on('b.section_id', '=', 'awb_mst_section.id')
                    ->where(DB::raw('md5(b.id)'), '=', null)
                    ->where('awb_mst_section.platform_id', '=', 'b.platform_id');
            })
            ->where('awb_mst_section.platform_id', '=', $platform_id)
            ->where('awb_mst_section.flag_active', '=', 1)
            ->whereIn('awb_mst_section.id',[2,3,4,5,6,7]);

            try {
                // $data = $query->toSql();
                $data = $query->get();
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

    public function rsSidebarMenuLevelList(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $menu_id = $request->input('menu_id');
        $section_id = $request->input('section_id');

        if(isset($section_id)){
            $query = awb_mst_section::selectRaw("title,
                    md5(id) as md5id,
                    CASE WHEN md5(id) = ? and id not in (6,7) THEN 1 ELSE 0 END show_submenu,
                    'section' as controller",[$section_id])
                    ->whereIn('id',[2,3,4,5,6,7])
                    ->where('flag_active','=',1)
                    ->where('platform_id', '=', $platform_id);
        }else{
            $query = awb_mst_section::select('b.title',
                    DB::raw('md5(b.id) as md5id'),
                    DB::raw('1 as show_submenu'),
                    DB::raw("'page' as controller")
                )->leftJoin('awb_mst_menu as b', 'b.section_id', '=', 'awb_mst_section.id')
                ->where('awb_mst_section.platform_id', '=', $platform_id)
                ->where('awb_mst_section.flag_active', '=', 1)
                ->when(isset($menu_id), function($query) use($menu_id){
                    $query->where(DB::raw('md5(b.id)'), '=', $menu_id);
                })
                ->whereNotNull('b.id')
                ->orderBy('b.title');
        }

        try {
            //$data = $query->toSql();
            $data = $query->get();
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

    public function rsSidebarCategoryList(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $category = $request->input('category');
        $menu_id = $request->input('menu_id');
        $section_id = $request->input('section_id');
		$enable_iqos_delivery = awb_mst_config::where('_code', '=', 'enable_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
		$category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');

        if(isset($section_id)){
            $query = awb_mst_menu::select(
                'title',
                DB::raw('md5(id) as md5id'),
                DB::raw('0 as flag_active'),
                DB::raw("'menu' as controller")
            )
            ->where([[DB::raw('md5(section_id)'),'=',$section_id],['flag_active', '=', 1]])
            ->orderBy('title');
        }else{
            $query = awb_trn_category::selectRaw("title,
                md5(id) as md5id,
                'cate' as controller,
                CASE when md5(id) = ? then 1 else 0 end as flag_active",
                [$category]
            )->where('awb_trn_category.platform_id', '=', $platform_id)
            ->where('awb_trn_category.flag_active', '=', 1)
            ->where(DB::raw('md5(menu_id)'), '=', $menu_id)
            ->where('menu_id', '<>', 26)
            ->when($enable_iqos_delivery == 'FALSE', function($query) use($category_iqos_delivery){
                $query->where('id', '<>', $category_iqos_delivery);
            })
            ->orderBy('title');
        }

        try {
            $data = $query->get();
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

    public function rsSidebarCategory4List(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $category_id = $request->input('category_id');
        $menu_id = $request->input('menu_id');

    	if($category_id == null)
	    {
            $query = awb_trn_article::distinct()->select('category_4')
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'awb_trn_article.category_id')
                ->where('awb_trn_article.platform_id', '=', $platform_id)
                ->where('category_4', '<>', '')
                ->where(DB::raw('md5(b.menu_id)'), '=', $menu_id)
                ->orderBy('category_4');
        } else {
            $query = awb_trn_article::distinct()->select('category_4')
                ->where('awb_trn_article.platform_id', '=', $platform_id)
                ->where('category_4', '<>', '')
                ->where(DB::raw('md5(category_id)'), '=', $category_id)
                ->orderBy('category_4');
        }
        try {
            $data = $query->get();
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

    public function SubmitIdea(Request $request)
	{
        $paramFromBackend = $request->all();
        try {
            $param = [
                'message_idea'=> $paramFromBackend['userName'],
                'user_created'=> $paramFromBackend['userId'],
                'date_created'=> DB_global::Global_CurrentDatetime(),
                'user_modified'=> $paramFromBackend['userId'],
                'date_modified'=> DB_global::Global_CurrentDatetime(),
                '_status_data'=> 'Submitted',
                'message_idea'=> $paramFromBackend['message'],
                'platform_id' => $paramFromBackend['platform_id'],
            ];

            DB_global::cz_insert('awb_trn_submit_idea', $param);

            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function GetArticleDetail(Request $request)
    {
        $id = $request->input('id');
        $query = awb_trn_article::where('id', '=', $id);
        try {
            $data = $query->get();
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

    public function ValidateShareArticle(Request $request)
    {
        $articleIdmd5 = $request->input('articleIdmd5');
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id  = $userData->id;
        $platform_id = $request->input('platform_id');
        $data = null;
        try {
            if(awb_trn_article_share::where('trn_article_id', '=', $articleIdmd5)
                ->where('platform_id', '=', $platform_id)
                ->where('user_id', '=', $user_id)
                ->exists()){
                    $data = false;
                    $configShareArticle = 0;
            } else {
                $data = true;
                $configShareArticle = awb_mst_config::where('_code', '=', 'SHARE_ARTICLE')
                    ->where('platform_id', '=', $platform_id)
                    ->value('value');
            };
            return response()->json([
                'data' => $data,
                'point' => $configShareArticle,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function FaqList(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $data = awb_mst_faq::select('*')
                ->where('flag_active', '=', 1)
                ->where('platform_id', '=', $platform_id)
                ->orderBy('sort_index')
                ->get();
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

    public function ContentNetworkSubmitList(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            $data = awb_mst_content_your_own_network::select('*')
                ->where('flag_active', '=', 1)
                ->where('platform_id', '=', $platform_id)
                ->orderBy('sort_index')
                ->get();
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

    public function CheckAndGetDataIqosQuiz(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        try {
            $configIqosId = awb_mst_config::where('_code', '=', 'article_iqos_delivery_quiz')
                ->where('platform_id', '=', $platform_id)
                ->value('value');
            $sql = "SELECT b.article_id, a.trn_article_id as id, count(c.id) as total_user_answer
                FROM awb_trn_quiz a
                LEFT JOIN awb_trn_article b ON a.trn_article_id = b.id
                LEFT JOIN awb_trn_quiz_user c ON a.id = c.trn_quiz_id AND c.user_modified = :user_id
                WHERE a.trn_article_id = :configIqosId
                AND a.platform_id = :platform_id
                GROUP BY b.article_id, a.trn_article_id";
            $param = (['user_id' => $user_id,
                'platform_id' => $platform_id,
                'configIqosId' => $configIqosId
            ]);
            $data = DB_global::cz_result_array($sql, $param);
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

    public function getActivePeriod(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            $data = awb_mst_reg_period::select('*')
                ->where('reg_from', '<=', DB_global::Global_CurrentDate())
                ->where('reg_to', '>=', DB_global::Global_CurrentDate())
                ->where('platform_id', '=', $platform_id)
                ->orderBy('reg_from', 'DESC')
                ->first();
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

    public function InsertArticleLog(Request $request)
    {
        $articleIdmd5 = $request->input('articleIdmd5');
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');

        try {
            if(awb_trn_article_log::where('trn_article_id', '=', $articleIdmd5)
                ->where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->doesntExist()){
                    $articleId = awb_trn_article::where('id', '=', $articleIdmd5)
                        ->where('platform_id', '=', $platform_id)
                        ->value('id');
                    $array_data = array(
                        'user_id' => $user_id,
                        'trn_article_id'=> $articleId,
                        'date_read' => DB_global::Global_CurrentDatetime(),
                        'platform_id' => $platform_id
                    );
                    DB_global::cz_insert('awb_trn_article_log', $array_data);
                    $message = 'log insert success';
                    $data = true;
                } else {
                    $data = false;
                    $message = 'log insert failed';
                }
            return response()->json([
                'data' => $data,
                'message' => $message
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function AwbGenerateLog(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $log_type = $request->input('log_type');
        $log_info = $request->input('log_info');
        $access_device = $request->input('access_device');
        $transaction_id = $request->input('transaction_id');
        $platform_id = $request->input('platform_id');
        try {
            $arrayLog = [
                'user_name' => $userData->name,
                'user_id' => $userData->id,
                'user_account' => $userData->account,
                'user_email' => $userData->email,
                'log_type' => $log_type,
                'log_info' => $log_info,
                'log_date' => DB_global::Global_CurrentDatetime(),
                'access_device' => $access_device,
                'transaction_id' => $transaction_id,
                'platform_id' => $platform_id,
            ];
            DB_global::cz_insert('awb_trn_log', $arrayLog);
            return response()->json([
                'data' => true,
                'message' => 'log insert success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'log insert failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function HomeQuiz(Request $request){
        $id = $request->input('id');
        $iqosQuiz = $request->input('iqosQuiz');

        try {
            $data = awb_trn_quiz::where([
                ['flag_active','=', 1],
                ['trn_article_id','=',$id]
            ])->when($iqosQuiz == 1, function ($query) {
                return $query->inRandomOrder();
            }, function ($query) {
                return $query->orderBy('idx', 'asc');
            })
            ->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function RelatedTopic(Request $request){
        try {
            $menu_id = $request->input('menu_id');
            $platform_id = $request->input('platform_id');
            $data = awb_mst_menu::select(
                DB::raw('md5(awb_mst_menu.id) as awb_mst_menu_id'),
                'awb_mst_menu.title as awb_mst_menu_id_title',
                'awb_trn_category.id as awb_trn_category_id',
                'awb_trn_category.title as awb_trn_category_title'
            )->leftjoin('awb_trn_category', 'awb_mst_menu.id', '=', 'awb_trn_category.menu_id')
            ->where([
                ['awb_mst_menu.flag_active','=',1],
                ['awb_mst_menu.id','>=',36],
                ['awb_mst_menu.id','<=',38],
                ['awb_mst_menu.id','!=',$menu_id],
                ['awb_mst_menu.platform_id','=',$platform_id]
            ])
            ->groupBy('awb_mst_menu.id')->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function HomeSlider(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        try {
            $subQuiz = awb_trn_quiz_user::select('trn_article_id')
                ->where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->distinct();

            $query = DB::table('awb_mst_slider as a')
                ->select('a.*',
                DB::raw('max(b.flag_quiz) as flag_quiz'),
                DB::raw('max(b.id) as trn_article_id'),
                DB::raw('max(q.trn_article_id) as awb_trn_quiz_article_id'),
                DB::raw('CASE WHEN max(b.flag_quiz) =  1 and max(q.trn_article_id) is null
                    then 1 else 0
                    end as show_quiz')
            )->leftJoin('awb_trn_article as b', 'b.article_id', '=', 'a.article_id')
            ->leftJoinSub($subQuiz, 'q', function($join){
                $join->on('q.trn_article_id','=', 'b.id')
                ->where('b.flag_active', '=', 1);
            })
            ->where('a.platform_id', '=', $platform_id)
            ->where('a.flag_active','=', 1)
            ->groupBy('a.id')
            ->orderBy('a.sort_index', 'DESC');

            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function HomeEvent(Request $request)
	{
        $platform_id = $request->input('platform_id');
        try {
            $query = DB::table('awb_trn_event')
                ->where('platform_id', '=', $platform_id)
                ->where('flag_active','=', 1)
                ->orderBy('sort_index');
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListArticleBySubCategoryIdOftheMonth(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $sub_category_id = $request->input('sub_category_id');
        $user_id = $request->input('user_id');

        try {
            $subQuiz = awb_trn_quiz_user::distinct()->select('trn_article_id')
                ->where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id);
            $subLog = awb_trn_article_log::select('trn_article_id', 'date_read')
                ->where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id);

            $query = DB::table('awb_trn_article as a')
                ->select('a.*',
                    'b.title as category_title',
                    DB::raw('CASE WHEN a.flag_quiz = 1 AND x.trn_article_id is null THEN 1 ELSE 0 END AS show_quiz'),
                    DB::raw('CASE WHEN y.date_read is not null THEN 1 ELSE 0 END AS flag_read')
                )
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->leftJoinSub($subQuiz, 'x', function ($join){
                    $join->on('x.trn_article_id', '=', 'a.id');
                })
                ->leftJoinSub($subLog, 'y', function ($join){
                    $join->on('y.trn_article_id', '=', 'a.id');
                })
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.sub_category_id', '=', $sub_category_id)
                ->where('a.flag_active', '=', 1)
                ->where('a.flag_article_of_the_month', '=', 1)
                ->limit(8)
                ->orderBy('a.id', 'desc');
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListArticleWhats(Request $request)
    {
        /* ListArticleWhatsHot
        ListArticleWhatsNew
        ListArticleContentFromYourNetwork
        /* $type
        hot: ListArticleWhatsHot
        new: ListArticleWhatsNew
        network: ListArticleContentFromYourNetwork
        */
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $type = $request->input('type');

		$category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
        try {
            $subQuiz = awb_trn_quiz_user::distinct()->select('trn_article_id')
                ->where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id);
            $subLog = awb_trn_article_log::select('trn_article_id', 'date_read')
                ->where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id);
            $subSpesific = awb_trn_article_spesific_user::select('trn_article_id')
                ->where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id);

            $query = DB::table('awb_trn_article as a')
                ->select('a.*',
                    'b.title as category_title',
                    DB::raw('CASE WHEN a.flag_quiz = 1 AND x.trn_article_id is null THEN 1 ELSE 0 END AS show_quiz'),
                    DB::raw('CASE WHEN y.date_read is not null THEN 1 ELSE 0 END AS flag_read')
                )
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->when($type == 'hot', function($query){
                    $query->rightJoin('awb_dashboard as d', function ($join) {
                        $join->on('d.number_3', '=', 'a.id')
                            ->where('d.code', '=', 'whats hot');
                    });
                })
                ->when($type == 'network', function($query){
                    $query->leftJoin('awb_mst_menu as c', function ($join) {
                        $join->on('c.id', '=', 'b.menu_id');
                    });
                })
                ->leftJoinSub($subQuiz, 'x', function ($join){
                    $join->on('x.trn_article_id', '=', 'a.id');
                })
                ->leftJoinSub($subLog, 'y', function ($join){
                    $join->on('y.trn_article_id', '=', 'a.id');
                })
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', 1)
                ->where('b.flag_active', '=', 1)
                ->where('b.id', '<>', $category_iqos_delivery)
                ->when($type == 'hot', function($query){
                    $query->limit(8)
                        ->orderBy('d.number_1', 'desc');
                })
                ->when($type == 'network', function($query) use($subSpesific){
                    $query->where('c.section_id', '=', 7)
                        ->where(function($query) use($subSpesific){
                            $query->where('a.flag_show_spesific_user', '=', 0)
                                ->orWhereIn('a.id', $subSpesific);
                        })
                        ->limit(6)
                        ->orderBy('a.id', 'desc');
                })
                ->when($type == 'new', function($query){
                    $query->limit(8)
                        ->orderBy('a.id', 'desc');
                });
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GetPageContent(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $title = $request->input('title');

        try{
            $query = DB::table('awb_mst_page')
                ->where('platform_id', '=', $platform_id)
                ->where('title', '=', $title);
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function PointReset(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            DB::table('awb_mst_user_profile')->where('platform_id', '=', $platform_id)->delete();
            DB::table('awb_trn_point_history')->where('platform_id', '=', $platform_id)->delete();
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function HomeOurOtherSources(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try{
            $query = DB::table('awb_mst_sources')
                ->where('flag_active', '=', 1)
                ->where('platform_id', '=', $platform_id)
                ->orderBy('sort_index');
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function CheckAndSubmitQuiz($raQuizDetail, $userAnswer, $articleId, $user_id, $platform_id = 4)
    {
        $answer_choice_idx = '';
		$userPoint = 0;
		if($raQuizDetail->answer_mode == 3)
		{
			$answer_choice_idx = $raQuizDetail->answer_choice_mode_3;
			$userPoint = ($answer_choice_idx == $userAnswer ? $raQuizDetail->point : 0);
		}
		else
		{
			$answer_choice_idx = $raQuizDetail->answer_choice_idx;
			$userPoint = ($answer_choice_idx == $userAnswer ? $raQuizDetail->point : 0);
		}
		$array_data = array(
			'trn_quiz_id'=> $raQuizDetail->id,
			'trn_article_id'=> $articleId,
			'point'=> $raQuizDetail->point,
			'answer_flag_idx'=> $userAnswer,
			'answer_choice_idx'=> $answer_choice_idx,
			'answer_result'=> ($answer_choice_idx == $userAnswer ? 1 : 0),
			'user_modified'=> $user_id,
			'date_modified'=> DB_Global::Global_CurrentDatetime(),
            'platform_id' => $platform_id
		);
		DB_global::cz_insert('awb_trn_quiz_user', $array_data);
		return $userPoint;
    }

    public function SubmitQuiz(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $trn_article_id = $request->input('trn_article_id');
        $user_article_id = $request->input('user_article_id');
        $arrQuiz = $request->input('arrQuiz');
        $arrQuiz = json_decode($arrQuiz);
        try{
            /* QuizValidationSubmit */
            if(DB::table('awb_trn_quiz as a')
                ->leftJoin('awb_trn_quiz_user as b', function($join) use($user_id){
                    $join->on('b.trn_quiz_id', '=', 'a.id')
                        ->where('b.user_modified', '=', $user_id);
                })
                ->where('a.trn_article_id', '=', $trn_article_id)
                ->where('a.platform_id', '=', $platform_id)
                ->whereNull('b.id')->exists())
            {
                $userPoint = 0;
                $totalQuiz = 0;
                foreach($arrQuiz as $quizItem){
                    $userAnswer = '';
                    /* GetQuizDetail */
                    $raQuizDetail = awb_trn_quiz::where('id', '=', $quizItem->id)->where('platform_id', '=', $platform_id)->get();
                    $userAnswer = $quizItem->val;
                    if($userAnswer != '')
                    {
                        $totalQuiz +=1;
                        $userPoint = $userPoint + $this->CheckAndSubmitQuiz($raQuizDetail[0], $userAnswer, $trn_article_id, $user_id, $platform_id);
                    }
                }
                if($totalQuiz > 0)
                {
                    /* QuizUserUpdatePoint */
                    $array_data = array(
                        'user_id' => $user_id,
                        'point' => $userPoint,
                        'source' => 'answer quiz from article id : ' . $user_article_id,
                        'status_date' => DB_global::Global_CurrentDatetime(),
                        'user_modified' => $user_id,
                        'date_modified' => DB_global::Global_CurrentDatetime(),
                        'platform_id' => $platform_id,
                    );
                    DB_global::cz_insert('awb_trn_point_history', $array_data);
                    $data = AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);
                }

                /* RsQuizSummaryResult */
                $rsResultList = awb_trn_quiz::select('question', 'question_ind', 'answer_summary', 'answer_summary_ind',
                    DB::RAW("case when answer_choice_idx = 1 then choice_1 else choice_2 end as result")
                    )->where('trn_article_id', '=', $trn_article_id)
                    ->where('platform_id', '=', $platform_id)
                    ->where('answer_mode', '=', 1)
                    ->get();
                // print_r($rsResultList);

                $configSoundNtf = awb_mst_config::where('_code', '=', 'sound_notification')->where('platform_id', '=', $platform_id)->value('value');
                $data = array_merge($data, [
                    'configSoundNtf' => $configSoundNtf,
                    'userPoint' => $userPoint,
                    'rsResultList' => $rsResultList
                ]);
            } else {
                $data = false;
            }
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function QuizValidationSubmit(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $trn_article_id = $request->input('trn_article_id');

        try{
            $query = DB::table('awb_trn_quiz as a')
                ->leftJoin('awb_trn_quiz_user as b', function($join) use($user_id){
                    $join->on('b.trn_quiz_id', '=', 'a.id')
                        ->where('b.user_modified', '=', $user_id);
                })
                ->where('a.trn_article_id', '=', $trn_article_id)
                ->where('a.platform_id', '=', $platform_id)
                ->whereNull('b.id');
            $data = $query->exists();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GetQuizDetail(Request $request)
	{
        $platform_id = $request->input('platform_id');
        $id = $request->input('id');
        try{
            $query = awb_trn_quiz::where('id', '=', $id)
                ->where('platform_id', '=', $platform_id);
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function QuizUserInsertHistory(Request $request)
    {
        $all = $request->input();
        $all = array_merge($all, [
                'date_created' => DB_global::Global_CurrentDatetime(),
                'date_modified' => DB_global::Global_CurrentDatetime()
            ]);
        try {
            $data = DB_global::cz_insert('awb_trn_quiz_user', $all, TRUE);
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function QuizUserUpdatePoint($userPoint, $user_article_id, $user_id, $platform_id)
    {
        try {
            $array_data = array(
                'user_id' => $user_id,
                'point' => $userPoint,
                'source' => 'answer quiz from article id : ' . $user_article_id,
                'status_date' => DB_global::Global_CurrentDatetime(),
                'user_modified' => $user_id,
                'date_modified' => DB_global::Global_CurrentDatetime(),
                'platform_id' => $platform_id,
            );
            DB_global::cz_insert('awb_trn_point_history', $array_data);
            $data = AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);
            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListWorkShopSharing(Request $request)
    {
        try {
            $platform_id = $request->input('platform_id');
            $menu_id = $request->input('menu_id');
            $category_id = $request->input('category_id');
            $user_id = $request->input('user_id');
            $type = $request->input('type');

            $data = awb_trn_workshop_sharing::selectRaw(
                "awb_trn_workshop_sharing.*,
                awb_trn_category.title as awb_trn_category_title,
                (
                    select
                        awb_trn_workshop_sharing_user.register_type
                    from
                        awb_trn_workshop_sharing_user
                    where
                        awb_trn_workshop_sharing_user.awb_trn_workshop_sharing_id = awb_trn_workshop_sharing.id
                        and awb_trn_workshop_sharing_user.user_id = ?
                    limit 1
                ) as ready_in_this,
                (
                    select
                        count(awb_trn_workshop_sharing_user.awb_trn_workshop_sharing_id)
                    from
                        awb_trn_workshop_sharing_user
                    where
                        awb_trn_workshop_sharing_user.awb_trn_workshop_sharing_id = awb_trn_workshop_sharing.id
                    limit 1
                ) as total_user",[$user_id]
            )
            ->join('awb_trn_category', 'awb_trn_category.id','=','awb_trn_workshop_sharing.category_id')
            ->when(isset($menu_id), function($query) use($menu_id) {
                $query->join('awb_mst_menu', 'awb_mst_menu.id', '=','awb_trn_category.menu_id')
                ->where(DB::raw('md5(awb_trn_category.menu_id)'),'=',$menu_id);
            }, function($query) use($category_id) {
                $query->where(DB::raw('md5(awb_trn_workshop_sharing.category_id)'),'=',$category_id);
            })
            ->where('awb_trn_workshop_sharing.flag_active', '=', 1)
            ->when(isset($type)=='W', function($query) use($type) {
                $query->where('sub_category_type','=', $type);
            }, function($query) use($type) {
                $query->where('sub_category_type','=', $type);
            })
            ->where('awb_trn_workshop_sharing.platform_id', '=', $platform_id)
            ->orderBy('awb_trn_workshop_sharing.date_created')
            ->get();

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => [],
                'message' => "failed : $th"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function RsQuizSummaryResult(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $trn_article_id = $request->input('trn_article_id');
        try {
            $data = awb_trn_quiz::select('question', 'question_ind', 'answer_summary', 'answer_summary_ind',
                DB::RAW("case when answer_choice_idx = 1 then choice_1 else choice_2 end as result")
                )->where('trn_article_id', '=', $trn_article_id)
                ->where('platform_id', '=', $platform_id)
                ->where('answer_mode', '=', 1)
                ->get();

            return response()->json([
                'data' => $data,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'data' => [],
                'message' => "failed : $th"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ListskillfuturebyMenuId(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $menu_id = $request->input('menu_id');


            $query = awb_trn_category::select(
                "awb_trn_category.*", DB::RAW("md5(awb_trn_category.id) as pageId")
                )
                ->where('platform_id', '=', $platform_id)
                ->where('menu_id', '=', $menu_id);

            try {
                $data = $query->get();
                return response()->json([
                    'data' => $data,
                    'message' => 'success'
                ]);
            }catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => "failed : $th"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function SubmitShareArticle(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $platform_id = $request->input('platform_id');
        $user_id  = $userData->id;
        $trn_article_id = $request->input('trn_article_id');
        $configShareArticle = $request->input('configShareArticle');
        $participant = $request->input('participant');
        $arrParticipant = json_decode($participant);
        $configSendEmail = awb_mst_config::where('_code', '=', 'email_notification')->where('platform_id', '=', $platform_id)->value('value');
        try
        {
            $listParticipant = [];
            if(count($arrParticipant) > 0){
                foreach($arrParticipant as $partDtl){
                    $listParticipant = array_merge($listParticipant, [$partDtl->value]);
                }
                $rsListUser = DB::table('users')->select('id',
                        'email',
                        DB::raw('ifnull(name,full_name) as name')
                    )->where('status_active', '=', 1)
                    ->whereIn('id', $listParticipant)
                    ->get();
                $listEmail = '';
                foreach ($rsListUser as $drow)
                {
                    $listEmail = str_replace('~',',',$listEmail) . $drow->email . '~';
                }
                $arrayArticleShare = ([
                    'trn_article_id' => $trn_article_id,
                    'user_id' => $user_id,
                    'points' => $configShareArticle,
                    'share_email_to' => str_replace('~','', $listEmail),
                    'share_date' => DB_global::Global_CurrentDatetime(),
                    'flag_email' => ($configSendEmail == 'TRUE' ? 1 : 0),
                    'platform_id' => $platform_id,
                ]);
                DB_global::cz_insert('awb_trn_article_share', $arrayArticleShare);
                // print_r($arrayArticleShare);

                if($configShareArticle > 0)
                {
                    $arrayPointHistory = ([
                        'user_id' => $user_id,
                        'point' => $configShareArticle,
                        'source' => 'share article bonus point',
                        'status_date' => DB_global::Global_CurrentDatetime(),
                        'user_modified' => $user_id,
                        'date_modified' => DB_global::Global_CurrentDatetime(),
                        'platform_id' => $platform_id,
                    ]);
                    DB_global::cz_insert('awb_trn_point_history', $arrayPointHistory);
                    AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);
                }

                /* send email */
                if($configSendEmail == 'TRUE')
                {
                    foreach ($rsListUser as $drow)
                    {
                        if(isset($drow->email)){
                            $details = [
                                'user_id' => $user_id,
                                'trn_article_id' => $trn_article_id,
                                'toEmail' => $drow->email,
                                'toName' => $drow->name,
                            ];
                            SendEmailAwbShareArticle::dispatch($details);
                        }
                    }
                }
            }
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addPoint(Request $request)
    {
        $platform_id = $request->input('platform_id');
        $user_id = $request->input('user_id');
        $configShareArticle = $request->input('configShareArticle');
        try {
            $array_data = array(
                'user_id' => $user_id,
                'point' => $configShareArticle,
                'source' => 'we miss you bonus point',
                'status_date' => DB_global::Global_CurrentDatetime(),
                'user_modified' => $user_id,
                'date_modified' => DB_global::Global_CurrentDatetime(),
                'platform_id' => $platform_id,
            );
            DB_global::cz_insert('awb_trn_point_history', $array_data);
            AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

	public function readLastAddPointWeMissYou(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id = $userData->id;
        $platform_id = $request->input('platform_id');
        $source = 'we miss you bonus point';
        $points = $request->input('points');
        $we_miss_you_point = awb_mst_config::where('_code', '=', 'WE_MISS_YOU_POINT')->where('platform_id', '=', $platform_id)->value('value');
        try {
            $useraccount = md5($userData->account.$user_id);
            if($useraccount == $points){
                $sql = "SELECT user_id, status_date, source FROM awb_trn_point_history
                    WHERE user_id = ? AND source = ? AND platform_id = ?
                    AND status_date >= date(date_sub(CURRENT_DATE, interval 30 day))
                    ORDER BY status_date DESC LIMIT 1 ";
    		    $data = DB_global::cz_result_array($sql, [$user_id, $source, $platform_id]);

                if(empty($data['user_id'])){
                    $array_data = array(
                        'user_id' => $user_id,
                        'point' => $we_miss_you_point,
                        'source' => $source,
                        'status_date' => DB_global::Global_CurrentDatetime(),
                        'user_modified' => $user_id,
                        'date_modified' => DB_global::Global_CurrentDatetime(),
                        'platform_id' => $platform_id,
                    );
                    DB_global::cz_insert('awb_trn_point_history', $array_data);
                    AwbGlobal::UpdateUserPointAndLevel($user_id, $platform_id);
                }
            }
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
	}

    public function trackThis(Request $request)
    {
        $token = $request->bearerToken();
        $userData = JWTAuth::toUser($token);
        $request_uri = $request->input('request_uri');
        $referer_page = $request->input('referer_page');
        $platform_id = $request->input('platform_id');

		$session_id = md5($token.$request->ip());
        try{
            $arrayTrack = [
                'session_id' => $session_id,
                'user_identifier' => $userData->id,
                'request_uri' => $request_uri,
                // 'timestamp' => time(), this column uses varchar to store datetime
                'client_ip' => $request->ip(),
                'client_user_agent' => $request->userAgent(),
                'referer_page' => $referer_page,
                'date_created' => DB_global::Global_CurrentDatetime(),
                'platform_id' => $platform_id,
            ];
            DB_global::cz_insert('awb_trn_user_tracking', $arrayTrack);
            return response()->json([
                'data' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function listArticleContentForYou(Request $request)
	{
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id = $userData->id;
        $platform_id = $request->input('platform_id');
        $category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
        try {
            $subQuiz = awb_trn_quiz_user::where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->distinct('trn_article_id');
            $subArticle = awb_trn_article_log::where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->whereNotNull('date_read')
                ->select('trn_article_id', 'date_read');

            $query = DB::table('awb_trn_article as a')
                ->selectRaw('a.*,
                    b.title as category_title,
                    case when a.flag_quiz = 1 and x.trn_article_id is null then 1 else 0 end as show_quiz,
                    case when y.date_read is not null then 1 else 0 end as flag_read')
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->join('awb_mst_menu as m', 'm.id', '=', 'b.menu_id')
                ->leftJoinSub($subQuiz, 'x', function($join){
                    $join->on('x.trn_article_id', '=', 'a.id');
                })
                ->leftJoinSub($subArticle, 'y', function($join){
                    $join->on('y.trn_article_id', '=', 'a.id');
                })
                ->where([
                    ['a.flag_active', '=', 1],
                    ['b.flag_active', '=', 1],
                    ['m.flag_active', '=', 1],
                    ['b.id', '<>', $category_iqos_delivery]
                ])
                ->where(function($query) use($user_id, $platform_id){
                    $query->whereIn('b.menu_id', function($query) use($user_id, $platform_id){
                        $query->select('topicid')
                            ->from('awb_user_pref_topic')
                            ->where('awb_user_pref_topic.userid', '=', $user_id)
                            ->where('awb_user_pref_topic.platform_id', '=', $platform_id);
                    })
                    ->orWhereIn('b.menu_id', function($query) use($user_id, $platform_id){
                        $query->select('menu_id')
                            ->from('awb_user_most_view')
                            ->where('awb_user_most_view.user_id', '=', $user_id)
                            ->where('awb_user_most_view.platform_id', '=', $platform_id);
                    });
                })
                ->inRandomOrder()
                ->limit(8);
            $data = $query->get();
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

    public function GetIqozUrl(Request $request)
    {
        $platform_id = $request->input('platform_id');
        try {
            $iqos_banner_url = awb_mst_config::where('_code', '=', 'IQOS_BANNER_URL')->where('platform_id', '=', $platform_id)->value('value');
            return response()->json([
                'data' => $iqos_banner_url,
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => false,
                'message' => 'failed: '.$th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function HomeCombo(Request $request)
    {
        $userData = AwbGlobal::getUserData($request->bearerToken());
        $user_id = $userData->id;
        $platform_id = $request->input('platform_id');
        try {
            $category_iqos_delivery = awb_mst_config::where('_code', '=', 'category_iqos_delivery')->where('platform_id', '=', $platform_id)->value('value');
            $subQuiz = awb_trn_quiz_user::where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->distinct('trn_article_id');
            $subArticle = awb_trn_article_log::where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->whereNotNull('date_read')
                ->select('trn_article_id', 'date_read');

            $listArticleContentForYou = DB::table('awb_trn_article as a')
                ->selectRaw('a.*,
                    b.title as category_title,
                    case when a.flag_quiz = 1 and x.trn_article_id is null then 1 else 0 end as show_quiz,
                    case when y.date_read is not null then 1 else 0 end as flag_read')
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->join('awb_mst_menu as m', 'm.id', '=', 'b.menu_id')
                ->leftJoinSub($subQuiz, 'x', function($join){
                    $join->on('x.trn_article_id', '=', 'a.id');
                })
                ->leftJoinSub($subArticle, 'y', function($join){
                    $join->on('y.trn_article_id', '=', 'a.id');
                })
                ->where([
                    ['a.flag_active', '=', 1],
                    ['b.flag_active', '=', 1],
                    ['m.flag_active', '=', 1],
                    ['b.id', '<>', $category_iqos_delivery]
                ])
                ->where(function($query) use($user_id, $platform_id){
                    $query->whereIn('b.menu_id', function($query) use($user_id, $platform_id){
                        $query->select('topicid')
                            ->from('awb_user_pref_topic')
                            ->where('awb_user_pref_topic.userid', '=', $user_id)
                            ->where('awb_user_pref_topic.platform_id', '=', $platform_id);
                    })
                    ->orWhereIn('b.menu_id', function($query) use($user_id, $platform_id){
                        $query->select('menu_id')
                            ->from('awb_user_most_view')
                            ->where('awb_user_most_view.user_id', '=', $user_id)
                            ->where('awb_user_most_view.platform_id', '=', $platform_id);
                    });
                })
                ->inRandomOrder()
                ->limit(8)
                ->get();

            $homeOurOtherSources = DB::table('awb_mst_sources')
                ->where('flag_active', '=', 1)
                ->where('platform_id', '=', $platform_id)
                ->orderBy('sort_index')
                ->get();

            $subQuiz = awb_trn_quiz_user::distinct()->select('trn_article_id')
                ->where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id);
            $subLog = awb_trn_article_log::select('trn_article_id', 'date_read')
                ->where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id);
            $subSpesific = awb_trn_article_spesific_user::select('trn_article_id')
                ->where('user_id', '=', $user_id)
                ->where('platform_id', '=', $platform_id);

            $whatsHot = DB::table('awb_trn_article as a')
                ->select('a.*',
                    'b.title as category_title',
                    DB::raw('CASE WHEN a.flag_quiz = 1 AND x.trn_article_id is null THEN 1 ELSE 0 END AS show_quiz'),
                    DB::raw('CASE WHEN y.date_read is not null THEN 1 ELSE 0 END AS flag_read')
                )
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->rightJoin('awb_dashboard as d', function ($join) {
                    $join->on('d.number_3', '=', 'a.id')
                        ->where('d.code', '=', 'whats hot');
                })
                ->leftJoinSub($subQuiz, 'x', function ($join){
                    $join->on('x.trn_article_id', '=', 'a.id');
                })
                ->leftJoinSub($subLog, 'y', function ($join){
                    $join->on('y.trn_article_id', '=', 'a.id');
                })
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', 1)
                ->where('b.flag_active', '=', 1)
                ->where('b.id', '<>', $category_iqos_delivery)
                ->limit(8)
                ->orderBy('d.number_1', 'desc')
                ->get();

            $whatsNew = DB::table('awb_trn_article as a')
                ->select('a.*',
                    'b.title as category_title',
                    DB::raw('CASE WHEN a.flag_quiz = 1 AND x.trn_article_id is null THEN 1 ELSE 0 END AS show_quiz'),
                    DB::raw('CASE WHEN y.date_read is not null THEN 1 ELSE 0 END AS flag_read')
                )
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->leftJoinSub($subQuiz, 'x', function ($join){
                    $join->on('x.trn_article_id', '=', 'a.id');
                })
                ->leftJoinSub($subLog, 'y', function ($join){
                    $join->on('y.trn_article_id', '=', 'a.id');
                })
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', 1)
                ->where('b.flag_active', '=', 1)
                ->where('b.id', '<>', $category_iqos_delivery)
                ->limit(8)
                ->orderBy('a.id', 'desc')
                ->get();
            
            $whatsNetwork = DB::table('awb_trn_article as a')
                ->select('a.*',
                    'b.title as category_title',
                    DB::raw('CASE WHEN a.flag_quiz = 1 AND x.trn_article_id is null THEN 1 ELSE 0 END AS show_quiz'),
                    DB::raw('CASE WHEN y.date_read is not null THEN 1 ELSE 0 END AS flag_read')
                )
                ->leftJoin('awb_trn_category as b', 'b.id', '=', 'a.category_id')
                ->leftJoin('awb_mst_menu as c', function ($join) {
                    $join->on('c.id', '=', 'b.menu_id');
                })
                ->leftJoinSub($subQuiz, 'x', function ($join){
                    $join->on('x.trn_article_id', '=', 'a.id');
                })
                ->leftJoinSub($subLog, 'y', function ($join){
                    $join->on('y.trn_article_id', '=', 'a.id');
                })
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', 1)
                ->where('b.flag_active', '=', 1)
                ->where('b.id', '<>', $category_iqos_delivery)
                ->where('c.section_id', '=', 7)
                ->where('a.flag_show_spesific_user', '=', 0)
                ->orWhereIn('a.id', $subSpesific)
                ->limit(6)
                ->orderBy('a.id', 'desc')
                ->get();

            $homeEvent = DB::table('awb_trn_event')
                ->where('platform_id', '=', $platform_id)
                ->where('flag_active','=', 1)
                ->orderBy('sort_index')
                ->get();

            $subQuiz = awb_trn_quiz_user::select('trn_article_id')
                ->where('user_modified', '=', $user_id)
                ->where('platform_id', '=', $platform_id)
                ->distinct();

            $homeSlider = DB::table('awb_mst_slider as a')
                ->select('a.*',
                DB::raw('max(b.flag_quiz) as flag_quiz'),
                DB::raw('max(b.id) as trn_article_id'),
                DB::raw('max(q.trn_article_id) as awb_trn_quiz_article_id'),
                DB::raw('CASE WHEN max(b.flag_quiz) =  1 and max(q.trn_article_id) is null
                    then 1 else 0
                    end as show_quiz')
                )->leftJoin('awb_trn_article as b', 'b.article_id', '=', 'a.article_id')
                ->leftJoinSub($subQuiz, 'q', function($join){
                    $join->on('q.trn_article_id','=', 'b.id')
                    ->where('b.flag_active', '=', 1);
                })
                ->where('a.platform_id', '=', $platform_id)
                ->where('a.flag_active', '=', 1)
                ->groupBy('a.id')
                ->orderBy('a.sort_index', 'desc')
                ->get();

            return response()->json([
                'data1' => $whatsHot,
                'data2' => $whatsNew,
                'data3' => $whatsNetwork,
                'data4' => $listArticleContentForYou,
                'data5' => $homeOurOtherSources,
                'data6' => $homeEvent,
                'data7' => $homeSlider,
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
