<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TTR\UserController;
use App\Http\Controllers\TTR\SliderController;
use App\Http\Controllers\TTR\SignatureController;
use App\Http\Controllers\TTR\PlatformController;
use App\Http\Controllers\TTR\AdsController;
use App\Http\Controllers\TTR\BehaviorController;
use App\Http\Controllers\TTR\PinnedController;
use App\Http\Controllers\TTR\ReportController;
use App\Http\Controllers\TTR\ThemeController;
use App\Http\Controllers\TTR\EmailCardController;
use App\Http\Controllers\TTR\CharityController;
use App\Http\Controllers\TTR\LinkSourceController;
use App\Http\Controllers\TTR\ActivityLogController;
use App\Http\Controllers\TTR\EnergyController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TTR\DashboardController;
use App\Http\Controllers\TTR\TestController;

//timetothink
//use App\Http\Controllers\TTT\UserController;
use App\Http\Controllers\TTT\ThinkSliderController;
use App\Http\Controllers\TTT\FeatureController;
use App\Http\Controllers\TTT\FunctionMappingController;
use App\Http\Controllers\TTT\SliderCommentController;
use App\Http\Controllers\TTT\ThinkActivityLogController;
use App\Http\Controllers\TTT\MonitorController;
use App\Http\Controllers\TTT\ReportController as thinkReport;
use App\Http\Controllers\TTT\ThemeController as thinkTheme;
use App\Http\Controllers\TTT\PlatformController as thinkplatform;
use App\Http\Controllers\TTT\UserController as thinkUser;
use App\Http\Controllers\TTT\InitiateController;
use App\Http\Controllers\TTT\MeetingController;

//menu
use App\Http\Controllers\Menu\MenuAppController as menuApp;
use App\Http\Controllers\Menu\MenuNewsController as menuNews;
use App\Http\Controllers\Menu\MenuSliderController as menuSlider;
use App\Http\Controllers\Menu\MenuTrnActivationController as menuTrnActivation;
use App\Http\Controllers\Menu\PlatformController as menuPlatform;
use App\Http\Controllers\Menu\UserController as menuUser;
use App\Http\Controllers\Menu\ThemeController as menuTheme;

//timetolisten
use App\Http\Controllers\TTL\DialogueSliderController;
use App\Http\Controllers\TTL\EventController;
use App\Http\Controllers\TTL\FeedbackController;
use App\Http\Controllers\TTL\GalleryController;
use App\Http\Controllers\TTL\InitiateController as dialogueInitiate;
use App\Http\Controllers\TTL\PlatformController as dialoguePlatform;
use App\Http\Controllers\TTL\QnaController;
use App\Http\Controllers\TTL\ScheduleController;
use App\Http\Controllers\TTL\ThemeController as dialogueTheme;
use App\Http\Controllers\TTL\UftConfigController;
use App\Http\Controllers\TTL\UftEmailCardController;
use App\Http\Controllers\TTL\UftFeatureController;
use App\Http\Controllers\TTL\UftSliderController;
use App\Http\Controllers\TTL\UserController as dialogueUser;
use App\Http\Controllers\TTL\UserHofController;
use App\Http\Controllers\TTL\YawaController;

use App\Http\Controllers\Findtime\ChallengeController;
use App\Http\Controllers\Findtime\DashboardController as ideationDashboard;
use App\Http\Controllers\Findtime\IdeationPostController;
use App\Http\Controllers\Findtime\IdeationReportController;
use App\Http\Controllers\Findtime\IdeationSliderController;
use App\Http\Controllers\Findtime\PlatformController as ideationPlatform;
use App\Http\Controllers\Findtime\ThemeController as ideationTheme;
use App\Http\Controllers\Findtime\UserController as ideationUser;

//DashboardFrontend
//use App\Http\Controllers\DashboardFrontend\AllAppsController;
use App\Http\Controllers\DashboardFrontend\UniqueUserController;
use App\Http\Controllers\DashboardFrontend\SessionUserController;
use App\Http\Controllers\DashboardFrontend\FunctionUserController;
use App\Http\Controllers\DashboardFrontend\LocationUserController;
use App\Http\Controllers\DashboardFrontend\GenerationUserController;
use App\Http\Controllers\DashboardFrontend\TTRBySignatureController;
use App\Http\Controllers\DashboardFrontend\TTRByBehaviorController;

use App\Http\Controllers\DashboardFrontend\ImportMenuUserInfoController;



//AWB - Learn
use App\Http\Controllers\AWB\PlatformController as awbPlatform;
use App\Http\Controllers\AWB\UserLevelController as awbUserLevel;
use App\Http\Controllers\AWB\WebConfigurationController as awbWebConfig;
use App\Http\Controllers\AWB\PagesController as awbPages;
use App\Http\Controllers\AWB\FaqController as awbFaq;
use App\Http\Controllers\AWB\UserController as awbUser;
use App\Http\Controllers\AWB\SliderController as awbSlider;
use App\Http\Controllers\AWB\SectionController as awbSection;
use App\Http\Controllers\AWB\MenuController as awbMenu;
use App\Http\Controllers\AWB\TextInfoController as awbTextInfo;
use App\Http\Controllers\AWB\CategoryController as awbCategory;
use App\Http\Controllers\AWB\SubCategoryController as awbSubCategory;
use App\Http\Controllers\AWB\ArticleController as awbArticle;
use App\Http\Controllers\AWB\SliderCategoryController as awbSliderCategory;
use App\Http\Controllers\AWB\WorkshopSharingController as awbWorkshopSharing;
use App\Http\Controllers\AWB\EventController as awbEvent;
use App\Http\Controllers\AWB\RewardController as awbReward;
use App\Http\Controllers\AWB\CalendarController as awbCalendar;
use App\Http\Controllers\AWB\SourceController as awbSource;
use App\Http\Controllers\AWB\LinkSourcesController as awbLinkSources;
use App\Http\Controllers\AWB\BadgeController as awbBadge;
use App\Http\Controllers\AWB\RedeemCodeController as awbRedeemCode;
use App\Http\Controllers\AWB\SubmittedArticleController as awbSubmittedArticle;
use App\Http\Controllers\AWB\CourseController as awbCourse;
use App\Http\Controllers\AWB\RegPeriodController as awbRegPeriod;
use App\Http\Controllers\AWB\PointHistoryController as awbPointHistory;
use App\Http\Controllers\AWB\SliderSffController as awbSliderSff;
use App\Http\Controllers\AWB\CurriculumController as awbPbcCurriculum;
use App\Http\Controllers\AWB\ModuleController as awbPbcModule;
use App\Http\Controllers\AWB\SubModController as awbPbcSubModule;
use App\Http\Controllers\AWB\ProexpController as awbPbcProexp;
use App\Http\Controllers\AWB\ExamController as awbPbcExam;
use App\Http\Controllers\AWB\QuestionController as awbPbcQuestion;
use App\Http\Controllers\AWB\PbcNotifController as awbPbcNotif;
use App\Http\Controllers\AWB\HofController as awbPbcHof;
use App\Http\Controllers\AWB\SwCurriculumController as awbSwCurriculum;
use App\Http\Controllers\AWB\SwModuleController as awbSwModule;
use App\Http\Controllers\AWB\SwSubModuleController as awbSwSubModule;
use App\Http\Controllers\AWB\SwExamController as awbSwExam;
use App\Http\Controllers\AWB\SwQuestionController as awbSwQuestion;
use App\Http\Controllers\AWB\SwExamScoreController as awbSwExamScore;
use App\Http\Controllers\AWB\SwCourseActivityController as awbSwCourseActivityScore;
use App\Http\Controllers\AWB\TrainingController as awbTraining;
use App\Http\Controllers\AWB\TrainingReportController as awbTrainingReport;
use App\Http\Controllers\AWB\TrainingScheduleController as awbTrainingSchedule;
use App\Http\Controllers\AWB\TermsController as awbTerms;
use App\Http\Controllers\AWB\WorkshopSharingUserController as awbWorkshopSharingUser;
use App\Http\Controllers\AWB\ArticleImportController as awbArticleImport;
use App\Http\Controllers\AWB\ActivityLogController as awbActivityLog;
use App\Http\Controllers\AWB\AnsweredQuizController as awbAnsweredQuiz;
use App\Http\Controllers\AWB\EmailSubscribeController as awbEmailSubscribe;
use App\Http\Controllers\AWB\RedeemRewardController as awbRedeemReward;
use App\Http\Controllers\AWB\RegisterCourseController as awbRegisterCourse;
use App\Http\Controllers\AWB\ShareArticleController as awbShareArticle;
use App\Http\Controllers\AWB\SubmittedIdeaController as awbSubmittedIdea;
use App\Http\Controllers\AWB\UserInfoController as awbUserInfo;
use App\Http\Controllers\AWB\QuizController as awbQuiz;

/* AWB non admin */
use App\Http\Controllers\AWB\HomeController as awbHome;
use App\Http\Controllers\AWB\RedeemController as awbRedeem;
use App\Http\Controllers\AWB\ProfileController as awbProfile;
use App\Http\Controllers\AWB\ViewCourseController as awbViewCourse;


//AWB - FindTalent
use App\Http\Controllers\FindTalent\PlatformController as findTalentPlatform;
use App\Http\Controllers\FindTalent\UserLevelController as findTalentUserLevel;
use App\Http\Controllers\FindTalent\UserController as findTalentUser;
use App\Http\Controllers\FindTalent\ThemeController as findTalentTheme;
use App\Http\Controllers\FindTalent\SliderController as findTalentSlider;
use App\Http\Controllers\FindTalent\ProjectController as findTalentProject;
use App\Http\Controllers\FindTalent\QuestionaireController as findTalentQuestionnaire;
use App\Http\Controllers\FindTalent\ReportController as findTalentReport;
use App\Http\Controllers\FindTalent\ActivityLogController as findtalentActivityLog;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('GetMd5', [Controller::class, 'GetMd5']);
// Route::post('GetCredential', [Controller::class, 'GetCredential']);
Route::post('MoveFile', [Controller::class, 'MoveFile']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::post('logout', [UserController::class, 'logout_now']);
Route::post('refreshToken', [UserController::class, 'refreshToken']);
//Route::get('book', [BookController::class, 'book']);

//add route here if function must need authentication
Route::middleware('jwt.verify')->group(function () {
    //from modal users
    Route::prefix('user')->group(function () {
        Route::get('user', [UserController::class, 'getAuthenticatedUser']);
        Route::post('UpdateData', [UserController::class, 'UpdateData']);
        Route::post('AddData', [UserController::class, 'AddData']);
        Route::post('ValidateId', [UserController::class, 'ValidateId']);
        Route::post('ListData', [UserController::class, 'ListData']);
        Route::post('SelectData', [UserController::class, 'SelectData']);
        Route::post('AddAdminRole', [UserController::class, 'AddAdminRole']);
        Route::post('CheckUserEmail', [UserController::class, 'CheckUserEmail']);
        Route::post('GetUserScore', [UserController::class, 'GetUserScore']);
        Route::post('GetTotalUserPost', [UserController::class, 'GetTotalUserPost']);
        Route::post('ActivityLog', [UserController::class, 'ActivityLog']);
        Route::post('UpdatePhotos', [UserController::class, 'UpdatePhotos']);
    });

    //controller slider
    Route::post('slider/ListData', [SliderController::class, 'ListData']);
    Route::post('slider/SelectData', [SliderController::class, 'SelectData']);
    Route::post('slider/RsSlider', [SliderController::class, 'RsSlider']);
    Route::post('slider/InsertData', [SliderController::class, 'InsertData']);
    Route::post('slider/UpdateData', [SliderController::class, 'UpdateData']);
    Route::post('slider/ValidateId', [SliderController::class, 'ValidateId']);
    Route::post('slider/ValidateHashtag', [SliderController::class, 'ValidateHashtag']);
    Route::post('slider/DeleteData', [SliderController::class, 'DeleteData']);

    //controller signature
    Route::post('signature/ListData', [SignatureController::class, 'ListData']);
    Route::post('signature/SelectData', [SignatureController::class, 'SelectData']);
    Route::post('signature/InsertData', [SignatureController::class, 'InsertData']);
    Route::post('signature/UpdateData', [SignatureController::class, 'UpdateData']);
    Route::post('signature/ValidateId', [SignatureController::class, 'ValidateId']);
    Route::post('signature/ValidateHashtag', [SignatureController::class, 'ValidateHashtag']);
    Route::post('signature/DeleteData', [SignatureController::class, 'DeleteData']);

    //controller platform
    Route::prefix('platform')->group(function () {
        Route::post('ListData', [PlatformController::class, 'ListData']);
        Route::post('SelectData', [PlatformController::class, 'SelectData']);
        Route::post('InsertData', [PlatformController::class, 'InsertData']);
        Route::post('UpdateData', [PlatformController::class, 'UpdateData']);
        Route::post('ValidateId', [PlatformController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [PlatformController::class, 'ValidateHashtag']);
        Route::get('GetAllCountry', [PlatformController::class, 'GetAllCountry']);
        Route::get('GetAllFunction', [PlatformController::class, 'GetAllFunction']);
        Route::post('GetAllEmployee', [PlatformController::class, 'GetAllEmployee']);
        Route::post('DeleteData', [PlatformController::class, 'DeleteData']);
        Route::post('GetPlatformAccess', [PlatformController::class, 'GetPlatformAccess']);
        Route::post('getRoleAdmin', [PlatformController::class, 'getRoleAdmin']);
        Route::post('getSideMenuReport', [PlatformController::class, 'getSideMenuReport']);
    });

    // Theme
    Route::prefix('theme')->group(function () {
        Route::post('ListData', [ThemeController::class, 'ListData']);
        Route::post('SelectData', [ThemeController::class, 'SelectData']);
        Route::post('InsertData', [ThemeController::class, 'InsertData']);
        Route::post('UpdateData', [ThemeController::class, 'UpdateData']);
        Route::post('ValidateId', [ThemeController::class, 'ValidateId']);
        Route::post('DeleteData', [ThemeController::class, 'DeleteData']);
        Route::post('SelectDataByPlatform', [ThemeController::class, 'SelectDataByPlatform']);
        Route::post('setAsDefault', [ThemeController::class, 'setAsDefault']);

    });

    //sheryant1
    /* ads */
    Route::post('ads/ListData', [AdsController::class, 'ListData']);
    Route::post('ads/SelectData', [AdsController::class, 'SelectData']);
    Route::post('ads/AdsImage', [AdsController::class, 'AdsImage']);
    Route::post('ads/AdsNewsfeedImage', [AdsController::class, 'AdsNewsfeedImage']);
    Route::post('ads/InsertData', [AdsController::class, 'InsertData']);
    Route::post('ads/UpdateData', [AdsController::class, 'UpdateData']);
    Route::post('ads/ValidateId', [AdsController::class, 'ValidateId']);
    Route::post('ads/ValidateHashtag', [AdsController::class, 'ValidateHashtag']);
    Route::post('ads/ClearAllIsPublished', [AdsController::class, 'ClearAllIsPublished']);
    Route::post('ads/DeleteData', [AdsController::class, 'DeleteData']);

    /* behavior */
    Route::post('behavior/RsSignature', [BehaviorController::class, 'RsSignature']);
    Route::post('behavior/RsBehaviorListBySignatureId', [BehaviorController::class, 'RsBehaviorListBySignatureId']);
    Route::post('behavior/RsListBehaviorSignature', [BehaviorController::class, 'RsListBehaviorSignature']);
    Route::post('behavior/ListData', [BehaviorController::class, 'ListData']);
    Route::post('behavior/SelectData', [BehaviorController::class, 'SelectData']);
    Route::post('behavior/InsertData', [BehaviorController::class, 'InsertData']);
    Route::post('behavior/UpdateData', [BehaviorController::class, 'UpdateData']);
    Route::post('behavior/ValidateId', [BehaviorController::class, 'ValidateId']);
    Route::post('behavior/CheckHashtag', [BehaviorController::class, 'CheckHashtag']);
    Route::post('behavior/ValidateHashtag', [BehaviorController::class, 'ValidateHashtag']);

    /* pinned */
    Route::prefix('pinned')->group(function () {
        Route::post('ListDataTab1', [PinnedController::class, 'ListDataTab1']);
        Route::post('ListDataTab2', [PinnedController::class, 'ListDataTab2']);
        Route::post('FlagAsPinned', [PinnedController::class, 'FlagAsPinned']);
        Route::post('RevokePinned', [PinnedController::class, 'RevokePinned']);
        Route::post('FormExport', [PinnedController::class, 'FormExport']);
    });

    /* report */
    Route::post('report/RsReceiversBehaviorTotal', [ReportController::class, 'RsReceiversBehaviorTotal']);
    Route::post('report/RsContributorsBehaviorTotal', [ReportController::class, 'RsContributorsBehaviorTotal']);
    Route::post('report/RsListBehaviorFilter', [ReportController::class, 'RsListBehaviorFilter']);
    Route::post('report/BehaviorUsage', [ReportController::class, 'BehaviorUsage']);
    Route::post('report/Report1', [ReportController::class, 'Report1']);
    Route::post('report/Report2', [ReportController::class, 'Report2']);
    Route::post('report/TopReceivers', [ReportController::class, 'TopReceivers']);
    Route::post('report/TopContributors', [ReportController::class, 'TopContributors']);
    Route::post('report/RawDataFormExport', [ReportController::class, 'RawDataFormExport']);
    Route::post('report/RawDataFormExportBackup', [ReportController::class, 'RawDataFormExportBackup']);

    /* energy */
    Route::post('energy/EnergySignature', [EnergyController::class, 'EnergySignature']);
    Route::post('energy/EnergyBehavior', [EnergyController::class, 'EnergyBehavior']);
    Route::post('energy/EnergySignatureExport', [EnergyController::class, 'EnergySignatureExport']);
    Route::post('energy/EnergyBehaviorExport', [EnergyController::class, 'EnergyBehaviorExport']);

    /* activity log */
    Route::prefix('activitylog')->group(function () {
        Route::post('ListData', [ActivityLogController::class, 'ListData']);
        Route::get('Export', [ActivityLogController::class, 'Export']);
        Route::post('FormExport', [ActivityLogController::class, 'FormExport']);
        Route::post('CountActivityLogUser', [ActivityLogController::class, 'CountActivityLogUser']);
    });

    /* email card */
    Route::prefix('emailcard')->group(function () {
        Route::post('ListData', [EmailCardController::class, 'ListData']);
        Route::post('GetApplicationList', [EmailCardController::class, 'GetApplicationList']);
        Route::post('SelectData', [EmailCardController::class, 'SelectData']);
        Route::post('InsertData', [EmailCardController::class, 'InsertData']);
        Route::post('UpdateData', [EmailCardController::class, 'UpdateData']);
        Route::post('ValidateId', [EmailCardController::class, 'ValidateId']);
        Route::post('DeleteData', [EmailCardController::class, 'DeleteData']);
        Route::post('MoveUp', [EmailCardController::class, 'MoveUp']);
        Route::post('MoveDown', [EmailCardController::class, 'MoveDown']);
    });

    /* charity */
    Route::prefix('charity')->group(function () {
        Route::post('ListData', [CharityController::class, 'ListData']);
        Route::post('SelectData', [CharityController::class, 'SelectData']);
        Route::post('InsertData', [CharityController::class, 'InsertData']);
        Route::post('UpdateData', [CharityController::class, 'UpdateData']);
        Route::post('DeleteData', [CharityController::class, 'DeleteData']);
        Route::post('GetActiveCharity', [CharityController::class, 'GetActiveCharity']);
        Route::post('GetActiveCharity2', [CharityController::class, 'GetActiveCharity2']);
    });

    /* dashboard */
    Route::prefix('dashboard')->group(function () {
        Route::post('RsListNewsfeedPinnedAndAlgoritmByHierarchy', [DashboardController::class, 'RsListNewsfeed_PinnedAndAlgoritmByHierarchy']);
        Route::post('RsStatisticScore', [DashboardController::class, 'RsStatisticScore']);
        Route::post('RsListUserComment', [DashboardController::class, 'RsListUserComment']);
        Route::post('InsertComment', [DashboardController::class, 'InsertComment']);
        Route::post('GenerateLikePost', [DashboardController::class, 'GenerateLikePost']);
        Route::post('RsListNewsfeedAsReceiver', [DashboardController::class, 'RsListNewsfeedAsReceiver']);
        Route::post('RsListNewsfeedAsContributor', [DashboardController::class, 'RsListNewsfeedAsContributor']);
        Route::post('GetUserDataMentions', [DashboardController::class, 'GetUserDataMentions']);
        Route::post('SubmitUserPost', [DashboardController::class, 'SubmitUserPost']);
        Route::post('HitsEmailNotification', [DashboardController::class, 'HitsEmailNotification']);
    });
    /*link source */
    Route::prefix('linksource')->group(function () {
        Route::post('ListData', [LinkSourceController::class, 'ListData']);
        Route::post('SelectData', [LinkSourceController::class, 'SelectData']);
        Route::post('InsertData', [LinkSourceController::class, 'InsertData']);
        Route::post('UpdateData', [LinkSourceController::class, 'UpdateData']);
        Route::post('DeleteData', [LinkSourceController::class, 'DeleteData']);
        Route::post('CheckUserAccess', [LinkSourceController::class, 'CheckUserAccess']);
    });

    Route::prefix('test')->group(function () {
        Route::get('sendemail', [TestController::class, 'sendemail']);
    });
});
//Route::get('book', [BookController::class, 'book']);
//Route::get('bookall', [BookController::class, 'bookAuth'])->middleware('jwt.verify');

//TIME TO THINK ROUTE

Route::post('thinklogin', [thinkUser::class, 'login']);
Route::post('thinklogout', [thinkUser::class, 'logout_now']);
Route::post('thinkrefreshToken', [thinkUser::class, 'refreshToken']);

//add route here if function must need authentication
Route::middleware('jwt.verify')->group(function () {
    Route::prefix('thinkuser')->group(function () {
        Route::get('user', [thinkUser::class, 'getAuthenticatedUser']);
        Route::post('UpdateData', [thinkUser::class, 'UpdateData']);
        Route::post('AddData', [thinkUser::class, 'AddData']);
        Route::post('ValidateId', [thinkUser::class, 'ValidateId']);
        Route::post('ListData', [thinkUser::class, 'ListData']);
        Route::post('SelectData', [thinkUser::class, 'SelectData']);
        Route::post('ActivityLog', [thinkUser::class, 'ActivityLog']);
        Route::post('UpdatePhotos', [thinkUser::class, 'UpdatePhotos']);
    });

    Route::prefix('thinkSlider')->group(function () {
        Route::post('ListData', [ThinkSliderController::class, 'ListData']);
        Route::post('SelectData', [ThinkSliderController::class, 'SelectData']);
        Route::post('InsertData', [ThinkSliderController::class, 'InsertData']);
        Route::post('UpdateData', [ThinkSliderController::class, 'UpdateData']);
        Route::post('ValidateId', [ThinkSliderController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [ThinkSliderController::class, 'ValidateHashtag']);
        Route::post('DeleteData', [ThinkSliderController::class, 'DeleteData']);
    });

    Route::prefix('thinkFeature')->group(function () {
        Route::post('ListData', [FeatureController::class, 'ListData']);
        Route::post('SelectData', [FeatureController::class, 'SelectData']);
        Route::post('InsertData', [FeatureController::class, 'InsertData']);
        Route::post('UpdateData', [FeatureController::class, 'UpdateData']);
        Route::post('ValidateId', [FeatureController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [FeatureController::class, 'ValidateHashtag']);
    });

    Route::prefix('thinkFunctionMapping')->group(function () {
        Route::post('ListData', [FunctionMappingController::class, 'ListData']);
        Route::post('SelectData', [FunctionMappingController::class, 'SelectData']);
        Route::post('InsertData', [FunctionMappingController::class, 'InsertData']);
        Route::post('UpdateData', [FunctionMappingController::class, 'UpdateData']);
        Route::post('ValidateId', [FunctionMappingController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [FunctionMappingController::class, 'ValidateHashtag']);
        Route::post('DeleteData', [FunctionMappingController::class, 'DeleteData']);
    });

    Route::prefix('thinkSliderComment')->group(function () {
        Route::post('ListData', [SliderCommentController::class, 'ListData']);
        Route::post('SelectData', [SliderCommentController::class, 'SelectData']);
        Route::post('InsertData', [SliderCommentController::class, 'InsertData']);
        Route::post('UpdateData', [SliderCommentController::class, 'UpdateData']);
        Route::post('ValidateId', [SliderCommentController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [SliderCommentController::class, 'ValidateHashtag']);
        Route::post('DeleteData', [SliderCommentController::class, 'DeleteData']);
    });

    Route::prefix('thinkActivityLog')->group(function () {
        Route::post('ListData', [ThinkActivityLogController::class, 'ListData']);
        Route::post('FormExport', [ThinkActivityLogController::class, 'FormExport']);
    });

    // Theme
    Route::prefix('thinkTheme')->group(function () {
        Route::post('ListData', [thinkTheme::class, 'ListData']);
        Route::post('SelectData', [thinkTheme::class, 'SelectData']);
        Route::post('InsertData', [thinkTheme::class, 'InsertData']);
        Route::post('UpdateData', [thinkTheme::class, 'UpdateData']);
        Route::post('DeleteData', [thinkTheme::class, 'DeleteData']);
        Route::post('SelectDataByPlatform', [thinkTheme::class, 'SelectDataByPlatform']);
        Route::post('setAsDefault', [thinkTheme::class, 'setAsDefault']);

    });

    Route::prefix('thinkMonitor')->group(function () {
        Route::post('ViewData', [MonitorController::class, 'ViewData']);
    });

    Route::prefix('thinkReport')->group(function () {
        Route::post('ListDataRecord', [thinkReport::class, 'ListDataRecord']);
        Route::post('ListDataScore', [thinkReport::class, 'ListDataScore']);
        Route::post('FormExport', [thinkReport::class, 'FormExport']);
    });

    Route::prefix('thinkplatform')->group(function () {
        Route::post('ListData', [thinkplatform::class, 'ListData']);
        Route::post('SelectData', [thinkplatform::class, 'SelectData']);
        Route::post('InsertData', [thinkplatform::class, 'InsertData']);
        Route::post('UpdateData', [thinkplatform::class, 'UpdateData']);
        Route::post('ValidateId', [thinkplatform::class, 'ValidateId']);
        Route::post('ValidateHashtag', [thinkplatform::class, 'ValidateHashtag']);
        Route::get('GetAllCountry', [thinkplatform::class, 'GetAllCountry']);
        Route::get('GetAllFunction', [thinkplatform::class, 'GetAllFunction']);
        Route::post('GetAllEmployee', [thinkplatform::class, 'GetAllEmployee']);
        Route::post('DeleteData', [thinkplatform::class, 'DeleteData']);
        Route::post('GetPlatformAccess', [thinkplatform::class, 'GetPlatformAccess']);
        Route::post('getRoleAdmin', [thinkplatform::class, 'getRoleAdmin']);
    });

    Route::prefix('thinkInitiate')->group(function () {
        Route::post('GetUserParticipant', [InitiateController::class, 'GetUserParticipant']);
        Route::post('AddMember', [InitiateController::class, 'AddMember']);
        Route::post('FormSubmit', [InitiateController::class, 'FormSubmit']);
        Route::post('getDetail', [InitiateController::class, 'getDetail']);
    });

    Route::prefix('thinkMeeting')->group(function () {
        Route::post('ListData', [MeetingController::class, 'ListData']);
        Route::post('DeleteData', [MeetingController::class, 'DeleteData']);
    });
});


Route::post('menulogin', [menuUser::class, 'login']);
Route::post('menulogout', [menuUser::class, 'logout_now']);
Route::post('menurefreshToken', [menuUser::class, 'refreshToken']);

Route::middleware('jwt.verify')->group(function () {
    Route::prefix('menuApp')->group(function () {
        Route::post('ListData', [menuApp::class, 'ListData']);
        Route::post('InsertData', [menuApp::class, 'InsertData']);
        Route::post('UpdateData', [menuApp::class, 'UpdateData']);
        Route::post('ValidateId', [menuApp::class, 'ValidateId']);
        Route::post('SelectData', [menuApp::class, 'SelectData']);
        Route::post('DeleteData', [menuApp::class, 'DeleteData']);
        Route::post('MoveUp', [menuApp::class, 'MoveUp']);
        Route::post('MoveDown', [menuApp::class, 'MoveDown']);
    });
    Route::prefix('menuNews')->group(function () {
        Route::post('ListData', [menuNews::class, 'ListData']);
        Route::post('InsertData', [menuNews::class, 'InsertData']);
        Route::post('UpdateData', [menuNews::class, 'UpdateData']);
        Route::post('ValidateId', [menuNews::class, 'ValidateId']);
        Route::post('SelectData', [menuNews::class, 'SelectData']);
        Route::post('DeleteData', [menuNews::class, 'DeleteData']);
    });
    Route::prefix('menuSlider')->group(function () {
        Route::post('ListData', [menuSlider::class, 'ListData']);
        Route::post('InsertData', [menuSlider::class, 'InsertData']);
        Route::post('UpdateData', [menuSlider::class, 'UpdateData']);
        Route::post('ValidateId', [menuSlider::class, 'ValidateId']);
        Route::post('SelectData', [menuSlider::class, 'SelectData']);
        Route::post('DeleteData', [menuSlider::class, 'DeleteData']);
    });
    Route::prefix('menuTrnActivation')->group(function () {
        Route::post('ListData', [menuTrnActivation::class, 'ListData']);
        Route::post('InsertData', [menuTrnActivation::class, 'InsertData']);
        Route::post('UpdateData', [menuTrnActivation::class, 'UpdateData']);
        Route::post('ValidateId', [menuTrnActivation::class, 'ValidateId']);
        Route::post('SelectData', [menuTrnActivation::class, 'SelectData']);
        Route::post('DeleteData', [menuTrnActivation::class, 'DeleteData']);
    });
    Route::prefix('menuPlatform')->group(function () {
        Route::post('ListData', [menuPlatform::class, 'ListData']);
        Route::post('SelectData', [menuPlatform::class, 'SelectData']);
        Route::post('InsertData', [menuPlatform::class, 'InsertData']);
        Route::post('UpdateData', [menuPlatform::class, 'UpdateData']);
        Route::post('ValidateId', [menuPlatform::class, 'ValidateId']);
        Route::post('ValidateHashtag', [menuPlatform::class, 'ValidateHashtag']);
        Route::get('GetAllCountry', [menuPlatform::class, 'GetAllCountry']);
        Route::get('GetAllFunction', [menuPlatform::class, 'GetAllFunction']);
        Route::post('GetAllEmployee', [menuPlatform::class, 'GetAllEmployee']);
        Route::post('DeleteData', [menuPlatform::class, 'DeleteData']);
        Route::post('GetPlatformAccess', [menuPlatform::class, 'GetPlatformAccess']);
        Route::post('getRoleAdmin', [menuPlatform::class, 'getRoleAdmin']);
    });
    Route::prefix('menuUser')->group(function () {
        Route::get('user', [menuUser::class, 'getAuthenticatedUser']);
        Route::post('UpdateData', [menuUser::class, 'UpdateData']);
        Route::post('AddData', [menuUser::class, 'AddData']);
        Route::post('ValidateId', [menuUser::class, 'ValidateId']);
        Route::post('ListData', [menuUser::class, 'ListData']);
        Route::post('SelectData', [menuUser::class, 'SelectData']);
        Route::post('ActivityLog', [menuUser::class, 'ActivityLog']);
        Route::post('UpdatePhotos', [menuUser::class, 'UpdatePhotos']);
    });
    Route::prefix('menuTheme')->group(function () {
        Route::post('ListData', [menuTheme::class, 'ListData']);
        Route::post('SelectData', [menuTheme::class, 'SelectData']);
        Route::post('InsertData', [menuTheme::class, 'InsertData']);
        Route::post('UpdateData', [menuTheme::class, 'UpdateData']);
        Route::post('DeleteData', [menuTheme::class, 'DeleteData']);
        Route::post('SelectDataByPlatform', [menuTheme::class, 'SelectDataByPlatform']);
        Route::post('setAsDefault', [menuTheme::class, 'setAsDefault']);

    });
});

//time to listen
Route::post('dialogueLogin', [dialogueUser::class, 'login']);
Route::post('dialogueLogout', [dialogueUser::class, 'logout_now']);
Route::post('dialogueRefreshToken', [dialogueUser::class, 'refreshToken']);

Route::middleware('jwt.verify')->group(function () {

    Route::prefix('dialogueEvent')->group(function () {
        Route::post('ListData', [EventController::class, 'ListData']);
        Route::post('InsertData', [EventController::class, 'InsertData']);
        Route::post('UpdateData', [EventController::class, 'UpdateData']);
        Route::post('ValidateId', [EventController::class, 'ValidateId']);
        Route::post('DeleteData', [EventController::class, 'DeleteData']);
        Route::post('UpdateFlagCheckMark', [EventController::class, 'UpdateFlagCheckMark']);
        Route::post('FormExport', [EventController::class, 'FormExport']);
        Route::post('SendEmail', [EventController::class, 'SendEmail']);
        Route::post('CheckUserRegistered', [EventController::class, 'CheckUserRegistered']);
    });

    Route::prefix('dialogueSlider')->group(function () {
        Route::post('ListData', [DialogueSliderController::class, 'ListData']);
        Route::post('SelectData', [DialogueSliderController::class, 'SelectData']);
        Route::post('RsSlider', [DialogueSliderController::class, 'RsSlider']);
        Route::post('InsertData', [DialogueSliderController::class, 'InsertData']);
        Route::post('UpdateData', [DialogueSliderController::class, 'UpdateData']);
        Route::post('ValidateId', [DialogueSliderController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [DialogueSliderController::class, 'ValidateHashtag']);
        Route::post('DeleteData', [DialogueSliderController::class, 'DeleteData']);
    });

    Route::prefix('dialogueFeedback')->group(function () {
        Route::post('ListData', [FeedbackController::class, 'ListData']);
        Route::post('ListDataReport', [FeedbackController::class, 'ListDataReport']);
        Route::post('SelectData', [FeedbackController::class, 'SelectData']);
        Route::post('InsertData', [FeedbackController::class, 'InsertData']);
        Route::post('InsertFeedbackUser', [FeedbackController::class, 'InsertFeedbackUser']);
        Route::post('UpdateData', [FeedbackController::class, 'UpdateData']);
        Route::post('ValidateId', [FeedbackController::class, 'ValidateId']);
        Route::post('DeleteData', [FeedbackController::class, 'DeleteData']);
        Route::post('MoveUp', [FeedbackController::class, 'MoveUp']);
        Route::post('MoveDown', [FeedbackController::class, 'MoveDown']);
        Route::post('GenerateActivityFeedback', [FeedbackController::class, 'GenerateActivityFeedback']);
        Route::post('CheckFeedbackSession', [FeedbackController::class, 'CheckFeedbackSession']);
        Route::post('UpdateActivity', [FeedbackController::class, 'UpdateActivity']);
        Route::post('FlagReset', [FeedbackController::class, 'FlagReset']);
        Route::post('FormExport', [FeedbackController::class, 'FormExport']);
    });

    Route::prefix('dialogueGallery')->group(function () {
        Route::post('ListData', [GalleryController::class, 'ListData']);
        Route::post('ListAllGallery', [GalleryController::class, 'ListAllGallery']);
        Route::post('SelectData', [GalleryController::class, 'SelectData']);
        Route::post('InsertData', [GalleryController::class, 'InsertData']);
        Route::post('UpdateData', [GalleryController::class, 'UpdateData']);
        Route::post('AddData', [GalleryController::class, 'AddData']);
        Route::post('ValidateId', [GalleryController::class, 'ValidateId']);
        Route::post('DeleteData', [GalleryController::class, 'DeleteData']);
        Route::post('MoveUp', [GalleryController::class, 'MoveUp']);
        Route::post('MoveDown', [GalleryController::class, 'MoveDown']);
    });

    Route::prefix('dialogueInitiate')->group(function () {
        Route::post('ListData', [dialogueInitiate::class, 'ListData']);
        Route::post('InsertData', [dialogueInitiate::class, 'InsertData']);
        Route::post('UpdateFlagCheckMark', [dialogueInitiate::class, 'UpdateFlagCheckMark']);
        Route::post('FormExport', [dialogueInitiate::class, 'FormExport']);
        Route::post('CheckUserRegistered', [dialogueInitiate::class, 'CheckUserRegistered']);
    });

    Route::prefix('dialoguePlatform')->group(function () {
        Route::post('ListData', [dialoguePlatform::class, 'ListData']);
        Route::post('SelectData', [dialoguePlatform::class, 'SelectData']);
        Route::post('InsertData', [dialoguePlatform::class, 'InsertData']);
        Route::post('UpdateData', [dialoguePlatform::class, 'UpdateData']);
        Route::post('ValidateId', [dialoguePlatform::class, 'ValidateId']);
        Route::post('ValidateHashtag', [dialoguePlatform::class, 'ValidateHashtag']);
        Route::get('GetAllCountry', [dialoguePlatform::class, 'GetAllCountry']);
        Route::get('GetAllFunction', [dialoguePlatform::class, 'GetAllFunction']);
        Route::post('GetAllEmployee', [dialoguePlatform::class, 'GetAllEmployee']);
        Route::post('DeleteData', [dialoguePlatform::class, 'DeleteData']);
        Route::post('GetPlatformAccess', [dialoguePlatform::class, 'GetPlatformAccess']);
        Route::post('getRoleAdmin', [dialoguePlatform::class, 'getRoleAdmin']);
    });

    Route::prefix('dialogueQna')->group(function () {
        Route::post('ListData', [QnaController::class, 'ListData']);
        Route::post('ListAllQna', [QnaController::class, 'ListAllQna']);
        Route::post('SelectData', [QnaController::class, 'SelectData']);
        Route::post('InsertData', [QnaController::class, 'InsertData']);
        Route::post('UpdateData', [QnaController::class, 'UpdateData']);
        Route::post('AddData', [QnaController::class, 'AddData']);
        Route::post('ValidateId', [QnaController::class, 'ValidateId']);
        Route::post('DeleteData', [QnaController::class, 'DeleteData']);
        Route::post('MoveUp', [QnaController::class, 'MoveUp']);
        Route::post('MoveDown', [QnaController::class, 'MoveDown']);
    });

    Route::prefix('dialogueSchedule')->group(function () {
        Route::post('ListData', [ScheduleController::class, 'ListData']);
        Route::post('SelectData', [ScheduleController::class, 'SelectData']);
        Route::post('InsertData', [ScheduleController::class, 'InsertData']);
        Route::post('UpdateData', [ScheduleController::class, 'UpdateData']);
        Route::post('AddData', [ScheduleController::class, 'AddData']);
        Route::post('ValidateId', [ScheduleController::class, 'ValidateId']);
        Route::post('DeleteData', [ScheduleController::class, 'DeleteData']);
        Route::post('MoveUp', [ScheduleController::class, 'MoveUp']);
        Route::post('MoveDown', [ScheduleController::class, 'MoveDown']);
    });

    Route::prefix('dialogueTheme')->group(function () {
        Route::post('ListData', [dialogueTheme::class, 'ListData']);
        Route::post('SelectData', [dialogueTheme::class, 'SelectData']);
        Route::post('InsertData', [dialogueTheme::class, 'InsertData']);
        Route::post('UpdateData', [dialogueTheme::class, 'UpdateData']);
        Route::post('DeleteData', [dialogueTheme::class, 'DeleteData']);
        Route::post('SelectDataByPlatform', [dialogueTheme::class, 'SelectDataByPlatform']);
        Route::post('setAsDefault', [dialogueTheme::class, 'setAsDefault']);
    });

    Route::prefix('uftConfig')->group(function () {
        Route::post('ListData', [UftConfigController::class, 'ListData']);
        Route::post('SelectData', [UftConfigController::class, 'SelectData']);
        Route::post('InsertData', [UftConfigController::class, 'InsertData']);
        Route::post('UpdateData', [UftConfigController::class, 'UpdateData']);
        Route::post('ValidateId', [UftConfigController::class, 'ValidateId']);
        Route::post('DeleteData', [UftConfigController::class, 'DeleteData']);
    });

    Route::prefix('uftEmailCard')->group(function () {
        Route::post('ListData', [UftEmailCardController::class, 'ListData']);
        Route::post('SelectData', [UftEmailCardController::class, 'SelectData']);
        Route::post('InsertData', [UftEmailCardController::class, 'InsertData']);
        Route::post('UpdateData', [UftEmailCardController::class, 'UpdateData']);
        Route::post('ValidateId', [UftEmailCardController::class, 'ValidateId']);
        Route::post('DeleteData', [UftEmailCardController::class, 'DeleteData']);
        Route::post('MoveUp', [UftEmailCardController::class, 'MoveUp']);
        Route::post('MoveDown', [UftEmailCardController::class, 'MoveDown']);
    });

    Route::prefix('uftFeature')->group(function () {
        Route::post('ListData', [UftFeatureController::class, 'ListData']);
        Route::post('SelectData', [UftFeatureController::class, 'SelectData']);
        Route::post('InsertData', [UftFeatureController::class, 'InsertData']);
        Route::post('UpdateData', [UftFeatureController::class, 'UpdateData']);
        Route::post('ValidateId', [UftFeatureController::class, 'ValidateId']);
        Route::post('DeleteData', [UftFeatureController::class, 'DeleteData']);
        Route::post('MoveUp', [UftFeatureController::class, 'MoveUp']);
        Route::post('MoveDown', [UftFeatureController::class, 'MoveDown']);
    });

    Route::prefix('uftSlider')->group(function () {
        Route::post('ListData', [UftSliderController::class, 'ListData']);
        Route::post('SelectData', [UftSliderController::class, 'SelectData']);
        Route::post('RsSlider', [UftSliderController::class, 'RsSlider']);
        Route::post('InsertData', [UftSliderController::class, 'InsertData']);
        Route::post('UpdateData', [UftSliderController::class, 'UpdateData']);
        Route::post('ValidateId', [UftSliderController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [UftSliderController::class, 'ValidateHashtag']);
        Route::post('DeleteData', [UftSliderController::class, 'DeleteData']);
    });

    Route::prefix('dialogueUser')->group(function () {
        Route::get('user', [dialogueUser::class, 'getAuthenticatedUser']);
        Route::post('UpdateData', [dialogueUser::class, 'UpdateData']);
        Route::post('AddData', [dialogueUser::class, 'AddData']);
        Route::post('ValidateId', [dialogueUser::class, 'ValidateId']);
        Route::post('ListData', [dialogueUser::class, 'ListData']);
        Route::post('SelectData', [dialogueUser::class, 'SelectData']);
        Route::post('ActivityLog', [dialogueUser::class, 'ActivityLog']);
        Route::post('UpdatePhotos', [dialogueUser::class, 'UpdatePhotos']);
    });

    Route::prefix('dialogueUserHof')->group(function () {
        Route::post('ListData', [UserHofController::class, 'ListData']);
        Route::post('SelectData', [UserHofController::class, 'SelectData']);
        Route::post('InsertData', [UserHofController::class, 'InsertData']);
        Route::post('UpdateData', [UserHofController::class, 'UpdateData']);
        Route::post('ValidateId', [UserHofController::class, 'ValidateId']);
        Route::post('DeleteData', [UserHofController::class, 'DeleteData']);
        Route::post('GetAllFunction', [UserHofController::class, 'GetAllFunction']);
        Route::post('GetDistinctFunction', [UserHofController::class, 'GetDistinctFunction']);
        Route::post('GetAllEmployee', [UserHofController::class, 'GetAllEmployee']);
        Route::post('MoveUp', [UserHofController::class, 'MoveUp']);
        Route::post('MoveDown', [UserHofController::class, 'MoveDown']);
        Route::post('getListYawaHof', [UserHofController::class, 'getListYawaHof']);
    });

    Route::prefix('dialogueYawa')->group(function () {
        Route::post('ListData', [YawaController::class, 'ListData']);
        Route::post('InsertData', [YawaController::class, 'InsertData']);
        Route::post('UpdateData', [YawaController::class, 'UpdateData']);
        Route::post('FormExport', [YawaController::class, 'FormExport']);
    });

});

Route::post('ideationLogin', [ideationUser::class, 'login']);
Route::post('ideationLogout', [ideationUser::class, 'logout_now']);
Route::post('ideationRefreshToken', [ideationUser::class, 'refreshToken']);

Route::middleware('jwt.verify')->group(function () {

    Route::prefix('challenge')->group(function () {
        Route::post('ListData', [ChallengeController::class, 'ListData']);
        Route::post('ListDirectorate', [ChallengeController::class, 'ListDirectorate']);
        Route::post('SelectData', [ChallengeController::class, 'SelectData']);
        Route::post('SelectChallenge', [ChallengeController::class, 'SelectChallenge']);
        Route::post('GetStringSeperatedCommas', [ChallengeController::class, 'GetStringSeperatedCommas']);
        Route::post('RsSlider', [ChallengeController::class, 'RsSlider']);
        Route::post('InsertData', [ChallengeController::class, 'InsertData']);
        Route::post('InsertDirectorate', [ChallengeController::class, 'InsertDirectorate']);
        Route::post('UpdateData', [ChallengeController::class, 'UpdateData']);
        Route::post('DeleteData', [ChallengeController::class, 'DeleteData']);
        Route::post('DeleteDirectorate', [ChallengeController::class, 'DeleteDirectorate']);
        Route::post('MoveUp', [ChallengeController::class, 'MoveUp']);
        Route::post('MoveDown', [ChallengeController::class, 'MoveDown']);
    });

    Route::prefix('ideationDashboard')->group(function (){
        Route::post('RsListNewsfeed', [ideationDashboard::class, 'RsListNewsfeed']);
        Route::post('GenerateLikePost', [ideationDashboard::class, 'GenerateLikePost']);
        Route::post('InsertComment', [ideationDashboard::class, 'InsertComment']);
        Route::post('RsListUserComment', [ideationDashboard::class, 'RsListUserComment']);
    });

    Route::prefix('ideationPost')->group(function () {
        Route::post('InsertData', [IdeationPostController::class, 'InsertData']);
        Route::post('ListComment', [IdeationPostController::class, 'ListComment']);
        Route::post('FlagAsPinned', [IdeationPostController::class, 'FlagAsPinned']);
        Route::post('RevokePinned', [IdeationPostController::class, 'RevokePinned']);
        Route::post('ListData', [IdeationPostController::class, 'ListData']);
    });

    Route::prefix('ideationReport')->group(function () {
        Route::post('ListDataRawdata', [IdeationReportController::class, 'ListDataRawdata']);
        Route::post('ListDataChallenge', [IdeationReportController::class, 'ListDataChallenge']);
        Route::post('FormExport', [IdeationReportController::class, 'FormExport']);
    });

    Route::prefix('ideationSlider')->group(function () {
        Route::post('ListData', [IdeationSliderController::class, 'ListData']);
        Route::post('SelectData', [IdeationSliderController::class, 'SelectData']);
        Route::post('RsSlider', [IdeationSliderController::class, 'RsSlider']);
        Route::post('InsertData', [IdeationSliderController::class, 'InsertData']);
        Route::post('UpdateData', [IdeationSliderController::class, 'UpdateData']);
        Route::post('ValidateId', [IdeationSliderController::class, 'ValidateId']);
        Route::post('ValidateHashtag', [IdeationSliderController::class, 'ValidateHashtag']);
        Route::post('DeleteData', [IdeationSliderController::class, 'DeleteData']);
    });

    Route::prefix('ideationTheme')->group(function () {
        Route::post('ListData', [ideationTheme::class, 'ListData']);
        Route::post('SelectData', [ideationTheme::class, 'SelectData']);
        Route::post('InsertData', [ideationTheme::class, 'InsertData']);
        Route::post('UpdateData', [ideationTheme::class, 'UpdateData']);
        Route::post('DeleteData', [ideationTheme::class, 'DeleteData']);
        Route::post('SelectDataByPlatform', [ideationTheme::class, 'SelectDataByPlatform']);
        Route::post('setAsDefault', [ideationTheme::class, 'setAsDefault']);
    });

    Route::prefix('ideationUser')->group(function () {
        Route::get('user', [ideationUser::class, 'getAuthenticatedUser']);
        Route::post('UpdateData', [ideationUser::class, 'UpdateData']);
        Route::post('AddData', [ideationUser::class, 'AddData']);
        Route::post('ValidateId', [ideationUser::class, 'ValidateId']);
        Route::post('ListData', [ideationUser::class, 'ListData']);
        Route::post('SelectData', [ideationUser::class, 'SelectData']);
        Route::post('ActivityLog', [ideationUser::class, 'ActivityLog']);
        Route::post('UpdatePhotos', [ideationUser::class, 'UpdatePhotos']);
    });

    Route::prefix('ideationPlatform')->group(function () {
        Route::post('ListData', [ideationPlatform::class, 'ListData']);
        Route::post('SelectData', [ideationPlatform::class, 'SelectData']);
        Route::post('InsertData', [ideationPlatform::class, 'InsertData']);
        Route::post('UpdateData', [ideationPlatform::class, 'UpdateData']);
        Route::post('ValidateId', [ideationPlatform::class, 'ValidateId']);
        Route::post('ValidateHashtag', [ideationPlatform::class, 'ValidateHashtag']);
        Route::get('GetAllCountry', [ideationPlatform::class, 'GetAllCountry']);
        Route::get('GetAllFunction', [ideationPlatform::class, 'GetAllFunction']);
        Route::post('GetAllEmployee', [ideationPlatform::class, 'GetAllEmployee']);
        Route::post('DeleteData', [ideationPlatform::class, 'DeleteData']);
        Route::post('GetPlatformAccess', [ideationPlatform::class, 'GetPlatformAccess']);
        Route::post('getRoleAdmin', [ideationPlatform::class, 'getRoleAdmin']);
    });
});

 // dashboard frontend
 Route::middleware('jwt.verify')->group(function () {
    Route::prefix('dashboardFrontend')->group(function () {
    //    Route::post('unique-user', [AllAppsController::class, 'ListDataByDate']);
    //     Route::post('all-apps-by-unique-user', [AllAppsController::class, 'ListDataByUniqueUser']);
        Route::post('session-user', [SessionUserController::class, 'ListData']);
        Route::post('unique-user', [UniqueUserController::class, 'ListData']);
        Route::post('function-user', [FunctionUserController::class, 'ListData']);
        Route::post('location-user', [LocationUserController::class, 'ListData']);
        Route::post('generation-user', [GenerationUserController::class, 'ListData']);

        Route::post('ttr-by-signature', [TTRBySignatureController::class, 'ListData']);
        Route::post('ttr-by-behavior', [TTRByBehaviorController::class, 'ListData']);

        Route::post('menu-user-info', [ImportMenuUserInfoController::class, 'ListData']);
        Route::post('import-menu-user-info', [ImportMenuUserInfoController::class, 'ImportData']);
    });


});


 // AWB - Learn
Route::post('awblogin', [awbUser::class, 'login']);
Route::post('awblogout', [awbUser::class, 'logout_now']);
Route::post('awbrefreshToken', [awbUser::class, 'refreshToken']);

Route::middleware('jwt.verify')->group(function () {
    Route::prefix('awbUser')->group(function () {
        Route::post('CheckUserAccess', [awbUser::class, 'CheckUserAccess']);
        Route::post('getPlatform', [awbUser::class, 'getPlatform']);
        Route::get('user', [awbUser::class, 'getAuthenticatedUser']);
        Route::post('UpdateData', [awbUser::class, 'UpdateData']);
        Route::post('AddData', [awbUser::class, 'AddData']);
        Route::post('ValidateId', [awbUser::class, 'ValidateId']);
        Route::post('ListData', [awbUser::class, 'ListData']);
        Route::post('SelectData', [awbUser::class, 'SelectData']);
        Route::post('SelectDataByAccount', [awbUser::class, 'SelectDataByAccount']);
        Route::post('ActivityLog', [awbUser::class, 'ActivityLog']);
        Route::post('SubscribeEmail', [awbUser::class, 'SubscribeEmail']);
        Route::post('CheckReferralAccount', [awbUser::class, 'CheckReferralAccount']);
        Route::post('Unsubscribe', [awbUser::class, 'Unsubscribe']);
        Route::post('ListPointHistory', [awbUser::class, 'ListPointHistory']);
        Route::post('ListStreakLogin', [awbUser::class, 'ListStreakLogin']);
        Route::post('ListContentLevel', [awbUser::class, 'ListContentLevel']);
        Route::post('AdjustPoint', [awbUser::class, 'AdjustPoint']);
        Route::post('InsertUserContent', [awbUser::class, 'InsertUserContent']);
    });

    Route::prefix('awbPlatform')->group(function () {
        Route::post('ListData', [awbPlatform::class, 'ListData']);
        Route::post('SelectData', [awbPlatform::class, 'SelectData']);
        Route::post('InsertData', [awbPlatform::class, 'InsertData']);
        Route::post('UpdateData', [awbPlatform::class, 'UpdateData']);
        Route::post('ValidateId', [awbPlatform::class, 'ValidateId']);
        Route::post('ValidateHashtag', [awbPlatform::class, 'ValidateHashtag']);
        Route::get('GetAllCountry', [awbPlatform::class, 'GetAllCountry']);
        Route::get('GetAllFunction', [awbPlatform::class, 'GetAllFunction']);
        Route::post('GetAllEmployee', [awbPlatform::class, 'GetAllEmployee']);
        Route::post('DeleteData', [awbPlatform::class, 'DeleteData']);
        Route::post('GetPlatformAccess', [awbPlatform::class, 'GetPlatformAccess']);
        Route::post('getRoleAdmin', [awbPlatform::class, 'getRoleAdmin']);
    });

    Route::prefix('awbUserLevel')->group(function () {
        Route::post('ListData', [awbUserLevel::class, 'ListData']);
        Route::post('SelectData', [awbUserLevel::class, 'SelectData']);
        Route::post('InsertData', [awbUserLevel::class, 'InsertData']);
        Route::post('UpdateData', [awbUserLevel::class, 'UpdateData']);
        Route::post('DeleteData', [awbUserLevel::class, 'DeleteData']);
    });
    Route::prefix('awbWebConfig')->group(function () {
        Route::post('ListData', [awbWebConfig::class, 'ListData']);
        Route::post('SelectData', [awbWebConfig::class, 'SelectData']);
        Route::post('InsertData', [awbWebConfig::class, 'InsertData']);
        Route::post('UpdateData', [awbWebConfig::class, 'UpdateData']);
        Route::post('DeleteData', [awbWebConfig::class, 'DeleteData']);
        Route::post('configReferralPoint', [awbWebConfig::class, 'configReferralPoint']);
    });
    Route::prefix('awbPages')->group(function () {
        Route::post('ListData', [awbPages::class, 'ListData']);
        Route::post('SelectData', [awbPages::class, 'SelectData']);
        Route::post('InsertData', [awbPages::class, 'InsertData']);
        Route::post('UpdateData', [awbPages::class, 'UpdateData']);
        Route::post('DeleteData', [awbPages::class, 'DeleteData']);
    });
    Route::prefix('awbFaq')->group(function () {
        Route::post('ListData', [awbFaq::class, 'ListData']);
        Route::post('SelectData', [awbFaq::class, 'SelectData']);
        Route::post('InsertData', [awbFaq::class, 'InsertData']);
        Route::post('UpdateData', [awbFaq::class, 'UpdateData']);
        Route::post('DeleteData', [awbFaq::class, 'DeleteData']);
        Route::post('MoveDown', [awbFaq::class, 'MoveDown']);
        Route::post('MoveUp', [awbFaq::class, 'MoveUp']);
    });
    Route::prefix('awbSlider')->group(function () {
        Route::post('ListData', [awbSlider::class, 'ListData']);
        Route::post('SelectData', [awbSlider::class, 'SelectData']);
        Route::post('InsertData', [awbSlider::class, 'InsertData']);
        Route::post('UpdateData', [awbSlider::class, 'UpdateData']);
        Route::post('DeleteData', [awbSlider::class, 'DeleteData']);
        Route::post('MoveUp', [awbSlider::class, 'MoveUp']);
        Route::post('MoveDown', [awbSlider::class, 'MoveDown']);
    });
    Route::prefix('awbSection')->group(function () {
        Route::post('ListData', [awbSection::class, 'ListData']);
        Route::post('SelectData', [awbSection::class, 'SelectData']);
        Route::post('InsertData', [awbSection::class, 'InsertData']);
        Route::post('UpdateData', [awbSection::class, 'UpdateData']);
        Route::post('DeleteData', [awbSection::class, 'DeleteData']);
        Route::post('MoveDown', [awbSection::class, 'MoveDown']);
        Route::post('MoveUp', [awbSection::class, 'MoveUp']);
    });
    Route::prefix('awbMenu')->group(function () {
        Route::post('ListData', [awbMenu::class, 'ListData']);
        Route::post('SelectData', [awbMenu::class, 'SelectData']);
        Route::post('InsertData', [awbMenu::class, 'InsertData']);
        Route::post('UpdateData', [awbMenu::class, 'UpdateData']);
        Route::post('DeleteData', [awbMenu::class, 'DeleteData']);
        Route::post('ListSection', [awbMenu::class, 'ListSection']);
        Route::post('MenuSpecial', [awbMenu::class, 'MenuSpecial']);
    });
    Route::prefix('awbCategory')->group(function () {
        Route::post('ListData', [awbCategory::class, 'ListData']);
        Route::post('SelectData', [awbCategory::class, 'SelectData']);
        Route::post('InsertData', [awbCategory::class, 'InsertData']);
        Route::post('UpdateData', [awbCategory::class, 'UpdateData']);
        Route::post('DeleteData', [awbCategory::class, 'DeleteData']);
        Route::post('MoveDown', [awbCategory::class, 'MoveDown']);
        Route::post('MoveUp', [awbCategory::class, 'MoveUp']);
        Route::post('ListSectionMenu', [awbCategory::class, 'ListSectionMenu']);
        Route::post('ListMenu', [awbCategory::class, 'ListMenu']);
        Route::post('MenuSpecial', [awbCategory::class, 'MenuSpecial']);
    });

    Route::prefix('awbSubCategory')->group(function () {
        Route::post('ListData', [awbSubCategory::class, 'ListData']);
        Route::post('SelectData', [awbSubCategory::class, 'SelectData']);
        Route::post('InsertData', [awbSubCategory::class, 'InsertData']);
        Route::post('UpdateData', [awbSubCategory::class, 'UpdateData']);
        Route::post('DeleteData', [awbSubCategory::class, 'DeleteData']);
        Route::post('ListSectionMenu', [awbSubCategory::class, 'ListSectionMenu']);
        Route::post('MoveDown', [awbSubCategory::class, 'MoveDown']);
        Route::post('MoveUp', [awbSubCategory::class, 'MoveUp']);
    });
    Route::prefix('awbArticle')->group(function () {
        Route::post('ListData', [awbArticle::class, 'ListData']);
        Route::post('SelectData', [awbArticle::class, 'SelectData']);
        Route::post('InsertData', [awbArticle::class, 'InsertData']);
        Route::post('UpdateData', [awbArticle::class, 'UpdateData']);
        Route::post('DeleteData', [awbArticle::class, 'DeleteData']);
        Route::post('ListDataArticleByCategory', [awbArticle::class, 'ListDataArticleByCategory']);
        Route::post('ListSectionPinnedArticle', [awbArticle::class, 'ListSectionPinnedArticle']);
        Route::post('MoveUp', [awbArticle::class, 'MoveUp']);
        Route::post('MoveDown', [awbArticle::class, 'MoveDown']);
        Route::post('ReSortingIndex', [awbArticle::class, 'ReSortingIndex']);
        Route::post('ListCategory', [awbArticle::class, 'ListCategory']);
        Route::post('GetDetailSpecificUser', [awbArticle::class, 'GetDetailSpecificUser']);
    });
    Route::prefix('awbSliderCategory')->group(function () {
        Route::post('ListData', [awbSliderCategory::class, 'ListData']);
        Route::post('SelectData', [awbSliderCategory::class, 'SelectData']);
        Route::post('InsertData', [awbSliderCategory::class, 'InsertData']);
        Route::post('UpdateData', [awbSliderCategory::class, 'UpdateData']);
        Route::post('DeleteData', [awbSliderCategory::class, 'DeleteData']);
        Route::post('MoveDown', [awbSliderCategory::class, 'MoveDown']);
        Route::post('MoveUp', [awbSliderCategory::class, 'MoveUp']);
    });
    Route::prefix('awbTextInfo')->group(function () {
        Route::post('ListData', [awbTextInfo::class, 'ListData']);
        Route::post('SelectData', [awbTextInfo::class, 'SelectData']);
        Route::post('SelectDataByMenu', [awbTextInfo::class, 'SelectDataByMenu']);
        Route::post('InsertData', [awbTextInfo::class, 'InsertData']);
        Route::post('UpdateData', [awbTextInfo::class, 'UpdateData']);
        Route::post('DeleteData', [awbTextInfo::class, 'DeleteData']);
        Route::post('ListSection', [awbTextInfo::class, 'ListSection']);
        Route::post('SubmitDataCategory', [awbTextInfo::class, 'SubmitDataCategory']);
        Route::post('SubmitDataMenu', [awbTextInfo::class, 'SubmitDataMenu']);
    });
    Route::prefix('awbWorkshopSharing')->group(function () {
        Route::post('ListData', [awbWorkshopSharing::class, 'ListData']);
        Route::post('SelectData', [awbWorkshopSharing::class, 'SelectData']);
        Route::post('InsertData', [awbWorkshopSharing::class, 'InsertData']);
        Route::post('UpdateData', [awbWorkshopSharing::class, 'UpdateData']);
        Route::post('DeleteData', [awbWorkshopSharing::class, 'DeleteData']);
        Route::post('ListSection', [awbWorkshopSharing::class, 'ListSection']);
    });
    Route::prefix('awbEvent')->group(function () {
        Route::post('ListData', [awbEvent::class, 'ListData']);
        Route::post('SelectData', [awbEvent::class, 'SelectData']);
        Route::post('InsertData', [awbEvent::class, 'InsertData']);
        Route::post('UpdateData', [awbEvent::class, 'UpdateData']);
        Route::post('DeleteData', [awbEvent::class, 'DeleteData']);
        Route::post('MoveUp', [awbEvent::class, 'MoveUp']);
        Route::post('MoveDown', [awbEvent::class, 'MoveDown']);
    });
    Route::prefix('awbReward')->group(function () {
        Route::post('ListData', [awbReward::class, 'ListData']);
        Route::post('SelectData', [awbReward::class, 'SelectData']);
        Route::post('InsertData', [awbReward::class, 'InsertData']);
        Route::post('UpdateData', [awbReward::class, 'UpdateData']);
        Route::post('DeleteData', [awbReward::class, 'DeleteData']);
        Route::post('DropdownFunction', [awbReward::class, 'DropdownFunction']);
        Route::post('DropdownLevel', [awbReward::class, 'DropdownLevel']);
    });
    Route::prefix('awbSource')->group(function () {
        Route::post('ListData', [awbSource::class, 'ListData']);
        Route::post('SelectData', [awbSource::class, 'SelectData']);
        Route::post('InsertData', [awbSource::class, 'InsertData']);
        Route::post('UpdateData', [awbSource::class, 'UpdateData']);
        Route::post('DeleteData', [awbSource::class, 'DeleteData']);
        Route::post('MoveUp', [awbSource::class, 'MoveUp']);
        Route::post('MoveDown', [awbSource::class, 'MoveDown']);
    });
    Route::prefix('awbCalendar')->group(function () {
        Route::post('ListData', [awbCalendar::class, 'ListData']);
        Route::post('SelectData', [awbCalendar::class, 'SelectData']);
        Route::post('InsertData', [awbCalendar::class, 'InsertData']);
        Route::post('UpdateData', [awbCalendar::class, 'UpdateData']);
        Route::post('DeleteData', [awbCalendar::class, 'DeleteData']);
    });
    Route::prefix('awbLinkSources')->group(function () {
        Route::post('ListData', [awbLinkSources::class, 'ListData']);
        Route::post('SelectData', [awbLinkSources::class, 'SelectData']);
        Route::post('InsertData', [awbLinkSources::class, 'InsertData']);
        Route::post('UpdateData', [awbLinkSources::class, 'UpdateData']);
        Route::post('DeleteData', [awbLinkSources::class, 'DeleteData']);
    });
    Route::prefix('awbBadge')->group(function () {
        Route::post('ListData', [awbBadge::class, 'ListData']);
        Route::post('SelectData', [awbBadge::class, 'SelectData']);
        Route::post('InsertData', [awbBadge::class, 'InsertData']);
        Route::post('UpdateData', [awbBadge::class, 'UpdateData']);
        Route::post('DeleteData', [awbBadge::class, 'DeleteData']);
        Route::post('ListMember', [awbBadge::class, 'ListMember']);
        Route::post('MemberAdd', [awbBadge::class, 'MemberAdd']);
        Route::post('RemoveMember', [awbBadge::class, 'RemoveMember']);
    });
    Route::prefix('awbRedeemCode')->group(function () {
        Route::post('ListData', [awbRedeemCode::class, 'ListData']);
        Route::post('SelectData', [awbRedeemCode::class, 'SelectData']);
        Route::post('InsertData', [awbRedeemCode::class, 'InsertData']);
        Route::post('UpdateData', [awbRedeemCode::class, 'UpdateData']);
        Route::post('DeleteData', [awbRedeemCode::class, 'DeleteData']);
        Route::post('ListDataClaim', [awbRedeemCode::class, 'ListDataClaim']);
        Route::post('ExportDataClaim', [awbRedeemCode::class, 'ExportDataClaim']);
    });
    Route::prefix('awbSubmittedArticle')->group(function () {
        Route::post('ListData', [awbSubmittedArticle::class, 'ListData']);
        Route::post('SelectData', [awbSubmittedArticle::class, 'SelectData']);
        Route::post('InsertData', [awbSubmittedArticle::class, 'InsertData']);
        Route::post('UpdateData', [awbSubmittedArticle::class, 'UpdateData']);
        Route::post('DeleteData', [awbSubmittedArticle::class, 'DeleteData']);
    });
    Route::prefix('awbCourse')->group(function () {
        Route::post('ListData', [awbCourse::class, 'ListData']);
        Route::post('SelectData', [awbCourse::class, 'SelectData']);
        Route::post('InsertData', [awbCourse::class, 'InsertData']);
        Route::post('UpdateData', [awbCourse::class, 'UpdateData']);
        Route::post('DeleteData', [awbCourse::class, 'DeleteData']);
        Route::post('ListCategory', [awbCourse::class, 'ListCategory']);
    });
    Route::prefix('awbRegPeriod')->group(function () {
        Route::post('ListData', [awbRegPeriod::class, 'ListData']);
        Route::post('SelectData', [awbRegPeriod::class, 'SelectData']);
        Route::post('InsertData', [awbRegPeriod::class, 'InsertData']);
        Route::post('UpdateData', [awbRegPeriod::class, 'UpdateData']);
        Route::post('DeleteData', [awbRegPeriod::class, 'DeleteData']);
    });
    Route::prefix('awbPointHistory')->group(function () {
        Route::post('ListData', [awbPointHistory::class, 'ListData']);
        Route::post('ImportData', [awbPointHistory::class, 'ImportData']);
        Route::post('SelectData', [awbPointHistory::class, 'SelectData']);
        Route::post('InsertData', [awbPointHistory::class, 'InsertData']);
        Route::post('UpdateData', [awbPointHistory::class, 'UpdateData']);
        Route::post('DeleteData', [awbPointHistory::class, 'DeleteData']);
    });
    Route::prefix('awbSliderSff')->group(function () {
        Route::post('ListData', [awbSliderSff::class, 'ListData']);
        Route::post('SelectData', [awbSliderSff::class, 'SelectData']);
        Route::post('InsertData', [awbSliderSff::class, 'InsertData']);
        Route::post('UpdateData', [awbSliderSff::class, 'UpdateData']);
        Route::post('DeleteData', [awbSliderSff::class, 'DeleteData']);
        Route::post('MoveUp', [awbSliderSff::class, 'MoveUp']);
        Route::post('MoveDown', [awbSliderSff::class, 'MoveDown']);
    });


    Route::prefix('awbPbcCurriculum')->group(function () {
        Route::post('ListData', [awbPbcCurriculum::class, 'ListData']);
        Route::post('ListDataForSelectOption', [awbPbcCurriculum::class, 'ListDataForSelectOption']);
        Route::post('SelectData', [awbPbcCurriculum::class, 'SelectData']);
        Route::post('InsertData', [awbPbcCurriculum::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcCurriculum::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcCurriculum::class, 'DeleteData']);
    });
    Route::prefix('awbPbcModule')->group(function () {
        Route::post('ListData', [awbPbcModule::class, 'ListData']);
        Route::post('ListDataForSelectOption', [awbPbcModule::class, 'ListDataForSelectOption']);
        Route::post('SelectData', [awbPbcModule::class, 'SelectData']);
        Route::post('InsertData', [awbPbcModule::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcModule::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcModule::class, 'DeleteData']);
    });
    Route::prefix('awbPbcSubModule')->group(function () {
        Route::post('ListData', [awbPbcSubModule::class, 'ListData']);
        Route::post('SelectData', [awbPbcSubModule::class, 'SelectData']);
        Route::post('InsertData', [awbPbcSubModule::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcSubModule::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcSubModule::class, 'DeleteData']);
    });
    Route::prefix('awbPbcProexp')->group(function () {
        Route::post('ListData', [awbPbcProexp::class, 'ListData']);
        Route::post('SelectData', [awbPbcProexp::class, 'SelectData']);
        Route::post('InsertData', [awbPbcProexp::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcProexp::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcProexp::class, 'DeleteData']);
    });
    Route::prefix('awbPbcExam')->group(function () {
        Route::post('ListData', [awbPbcExam::class, 'ListData']);
        Route::post('ListDataForSelectOption', [awbPbcExam::class, 'ListDataForSelectOption']);
        Route::post('SelectData', [awbPbcExam::class, 'SelectData']);
        Route::post('InsertData', [awbPbcExam::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcExam::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcExam::class, 'DeleteData']);
    });
    Route::prefix('awbPbcQuestion')->group(function () {
        Route::post('ListData', [awbPbcQuestion::class, 'ListData']);
        Route::post('SelectData', [awbPbcQuestion::class, 'SelectData']);
        Route::post('InsertData', [awbPbcQuestion::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcQuestion::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcQuestion::class, 'DeleteData']);
    });
    Route::prefix('awbPbcNotif')->group(function () {
        Route::post('ListData', [awbPbcNotif::class, 'ListData']);
        Route::post('SelectData', [awbPbcNotif::class, 'SelectData']);
        Route::post('InsertData', [awbPbcNotif::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcNotif::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcNotif::class, 'DeleteData']);
    });
    Route::prefix('awbPbcHof')->group(function () {
        Route::post('ListData', [awbPbcHof::class, 'ListData']);
        Route::post('SelectData', [awbPbcHof::class, 'SelectData']);
        Route::post('InsertData', [awbPbcHof::class, 'InsertData']);
        Route::post('UpdateData', [awbPbcHof::class, 'UpdateData']);
        Route::post('DeleteData', [awbPbcHof::class, 'DeleteData']);
    });
    Route::prefix('awbSwCurriculum')->group(function () {
        Route::post('ListData', [awbSwCurriculum::class, 'ListData']);
        Route::post('ListDataForSelectOption', [awbSwCurriculum::class, 'ListDataForSelectOption']);
        Route::post('SelectData', [awbSwCurriculum::class, 'SelectData']);
        Route::post('InsertData', [awbSwCurriculum::class, 'InsertData']);
        Route::post('UpdateData', [awbSwCurriculum::class, 'UpdateData']);
        Route::post('DeleteData', [awbSwCurriculum::class, 'DeleteData']);
    });
    Route::prefix('awbSwModule')->group(function () {
        Route::post('ListData', [awbSwModule::class, 'ListData']);
        Route::post('ListDataForSelectOption', [awbSwModule::class, 'ListDataForSelectOption']);
        Route::post('SelectData', [awbSwModule::class, 'SelectData']);
        Route::post('InsertData', [awbSwModule::class, 'InsertData']);
        Route::post('UpdateData', [awbSwModule::class, 'UpdateData']);
        Route::post('DeleteData', [awbSwModule::class, 'DeleteData']);
    });
    Route::prefix('awbSwSubModule')->group(function () {
        Route::post('ListData', [awbSwSubModule::class, 'ListData']);
        Route::post('SelectData', [awbSwSubModule::class, 'SelectData']);
        Route::post('InsertData', [awbSwSubModule::class, 'InsertData']);
        Route::post('UpdateData', [awbSwSubModule::class, 'UpdateData']);
        Route::post('DeleteData', [awbSwSubModule::class, 'DeleteData']);
    });
    Route::prefix('awbSwExam')->group(function () {
        Route::post('ListData', [awbSwExam::class, 'ListData']);
        Route::post('ListDataForSelectOption', [awbSwExam::class, 'ListDataForSelectOption']);
        Route::post('SelectData', [awbSwExam::class, 'SelectData']);
        Route::post('InsertData', [awbSwExam::class, 'InsertData']);
        Route::post('UpdateData', [awbSwExam::class, 'UpdateData']);
        Route::post('DeleteData', [awbSwExam::class, 'DeleteData']);
    });
    Route::prefix('awbSwQuestion')->group(function () {
        Route::post('ListData', [awbSwQuestion::class, 'ListData']);
        Route::post('SelectData', [awbSwQuestion::class, 'SelectData']);
        Route::post('InsertData', [awbSwQuestion::class, 'InsertData']);
        Route::post('UpdateData', [awbSwQuestion::class, 'UpdateData']);
        Route::post('DeleteData', [awbSwQuestion::class, 'DeleteData']);
    });
    Route::prefix('awbSwExamScore')->group(function () {
        Route::post('ListData', [awbSwExamScore::class, 'ListData']);
        Route::post('SelectData', [awbSwExamScore::class, 'SelectData']);
        Route::post('InsertData', [awbSwExamScore::class, 'InsertData']);
        Route::post('UpdateData', [awbSwExamScore::class, 'UpdateData']);
        Route::post('DeleteData', [awbSwExamScore::class, 'DeleteData']);
    });
    Route::prefix('awbSwCourseActivityScore')->group(function () {
        Route::post('ListData', [awbSwCourseActivityScore::class, 'ListData']);
        Route::post('SelectData', [awbSwCourseActivityScore::class, 'SelectData']);
        Route::post('InsertData', [awbSwCourseActivityScore::class, 'InsertData']);
        Route::post('UpdateData', [awbSwCourseActivityScore::class, 'UpdateData']);
        Route::post('DeleteData', [awbSwCourseActivityScore::class, 'DeleteData']);
    });
    Route::prefix('awbTraining')->group(function () {
        Route::post('ListData', [awbTraining::class, 'ListData']);
        Route::post('ListDataForFO', [awbTraining::class, 'ListDataForFO']);
        Route::post('SelectData', [awbTraining::class, 'SelectData']);
        Route::post('InsertData', [awbTraining::class, 'InsertData']);
        Route::post('UpdateData', [awbTraining::class, 'UpdateData']);
        Route::post('DeleteData', [awbTraining::class, 'DeleteData']);
        Route::post('ListTrainingStatus', [awbTraining::class, 'ListTrainingStatus']);
        Route::post('status', [awbTraining::class, 'status']);
        Route::post('scheduleList', [awbTraining::class, 'scheduleList']);
        Route::post('cekStatusAllTraining', [awbTraining::class, 'cekStatusAllTraining']);
        Route::post('cekStatusTrainingUser', [awbTraining::class, 'cekStatusTrainingUser']);
        Route::post('cekLink', [awbTraining::class, 'cekLink']);
        Route::post('cekStatusTrainingUserInTeam', [awbTraining::class, 'cekStatusTrainingUserInTeam']);
        Route::post('ImportData', [awbTraining::class, 'ImportData']);
        Route::post('deleteEmployee', [awbTraining::class, 'deleteEmployee']);
        Route::post('moveEmployee', [awbTraining::class, 'moveEmployee']);
        Route::post('rsvp', [awbTraining::class, 'rsvp']);
        Route::post('hadir', [awbTraining::class, 'hadir']);
        Route::post('newUserChange', [awbTraining::class, 'newUserChange']);
        Route::post('changeStatusSupervisor', [awbTraining::class, 'changeStatusSupervisor']);
        Route::post('CountTrainingStatus', [awbTraining::class, 'CountTrainingStatus']);
        Route::post('showDate', [awbTraining::class, 'showDate']);
        Route::post('ImportDataEmployee', [awbTraining::class, 'ImportDataEmployee']);
        Route::post('ListDataScheduleUser', [awbTraining::class, 'ListDataScheduleUser']);
    });
    Route::prefix('awbTrainingReport')->group(function () {
        Route::post('ListData', [awbTrainingReport::class, 'ListData']);
        Route::post('TrainingDetail', [awbTrainingReport::class, 'TrainingDetail']);
        Route::post('ScheduleDetail', [awbTrainingReport::class, 'ScheduleDetail']);
        Route::post('ExportDataTraining', [awbTrainingReport::class, 'ExportDataTraining']);
        Route::post('ExportDataSchedule', [awbTrainingReport::class, 'ExportDataSchedule']);
        Route::post('ExportDataScheduleRange', [awbTrainingReport::class, 'ExportDataScheduleRange']);
    });
    Route::prefix('awbTrainingSchedule')->group(function () {
        Route::post('ListData', [awbTrainingSchedule::class, 'ListData']);
        Route::post('SelectData', [awbTrainingSchedule::class, 'SelectData']);
        Route::post('InsertData', [awbTrainingSchedule::class, 'InsertData']);
        Route::post('UpdateData', [awbTrainingSchedule::class, 'UpdateData']);
        Route::post('DeleteData', [awbTrainingSchedule::class, 'DeleteData']);
    });
    Route::prefix('awbTerms')->group(function () {
        Route::post('ListData', [awbTerms::class, 'ListData']);
        Route::post('SelectData', [awbTerms::class, 'SelectData']);
        Route::post('InsertData', [awbTerms::class, 'InsertData']);
        Route::post('UpdateData', [awbTerms::class, 'UpdateData']);
        Route::post('DeleteData', [awbTerms::class, 'DeleteData']);
        Route::post('MoveUp', [awbTerms::class, 'MoveUp']);
        Route::post('MoveDown', [awbTerms::class, 'MoveDown']);
    });
    Route::prefix('awbRedeem')->group(function () {
        Route::post('ListReward', [awbRedeem::class, 'ListReward']);
        Route::post('ListRewardFaq', [awbRedeem::class, 'ListRewardFaq']);
        Route::post('ListUserLevel', [awbRedeem::class, 'ListUserLevel']);
        Route::post('ClaimReward', [awbRedeem::class, 'ClaimReward']);
    });
    Route::prefix('awbHome')->group(function () {
        Route::post('MenuPageContent', [awbHome::class, 'MenuPageContent']);
        Route::post('ListArticleByMenuId', [awbHome::class, 'ListArticleByMenuId']);
        Route::post('LayoutPageList', [awbHome::class, 'LayoutPageList']);
        Route::post('LayoutSectionList', [awbHome::class, 'LayoutSectionList']);
        Route::post('LayoutMenuList', [awbHome::class, 'LayoutMenuList']);
        Route::post('LayoutCategoryList', [awbHome::class, 'LayoutCategoryList']);
        Route::post('LayoutComboList', [awbHome::class, 'LayoutComboList']);
        Route::post('rsSidebarMenuSearchList', [awbHome::class, 'rsSidebarMenuSearchList']);
        Route::post('rsSidebarMenuLevelList', [awbHome::class, 'rsSidebarMenuLevelList']);
        Route::post('rsSidebarCategoryList', [awbHome::class, 'rsSidebarCategoryList']);
        Route::post('rsSidebarCategory4List', [awbHome::class, 'rsSidebarCategory4List']);
        Route::post('SubmitIdea', [awbHome::class, 'SubmitIdea']);
        Route::post('GetArticleDetail', [awbHome::class, 'GetArticleDetail']);
        Route::post('ValidateShareArticle', [awbHome::class, 'ValidateShareArticle']);
        Route::post('FaqList', [awbHome::class, 'FaqList']);
        Route::post('ContentNetworkSubmitList', [awbHome::class, 'ContentNetworkSubmitList']);
        Route::post('CheckAndGetDataIqosQuiz', [awbHome::class, 'CheckAndGetDataIqosQuiz']);
        Route::post('getActivePeriod', [awbHome::class, 'getActivePeriod']);
        Route::post('InsertArticleLog', [awbHome::class, 'InsertArticleLog']);
        Route::post('AwbGenerateLog', [awbHome::class, 'AwbGenerateLog']);
        Route::post('HomeQuiz', [awbHome::class, 'HomeQuiz']);
        Route::post('RelatedTopic', [awbHome::class, 'RelatedTopic']);
        Route::post('HomeSlider', [awbHome::class, 'HomeSlider']);
        Route::post('HomeEvent', [awbHome::class, 'HomeEvent']);
        Route::post('ListArticleBySubCategoryIdOftheMonth', [awbHome::class, 'ListArticleBySubCategoryIdOftheMonth']);
        Route::post('ListArticleWhats', [awbHome::class, 'ListArticleWhats']);
        Route::post('ListWorkShopSharing', [awbHome::class, 'ListWorkShopSharing']);
        Route::post('GetPageContent', [awbHome::class, 'GetPageContent']);
        Route::post('PointReset', [awbHome::class, 'PointReset']);
        Route::post('HomeOurOtherSources', [awbHome::class, 'HomeOurOtherSources']);
        Route::post('SubmitQuiz', [awbHome::class, 'SubmitQuiz']);
        Route::post('QuizValidationSubmit', [awbHome::class, 'QuizValidationSubmit']);
        Route::post('GetQuizDetail', [awbHome::class, 'GetQuizDetail']);
        Route::post('QuizUserInsertHistory', [awbHome::class, 'QuizUserInsertHistory']);
        Route::post('QuizUserUpdatePoint', [awbHome::class, 'QuizUserUpdatePoint']);
        Route::post('RsQuizSummaryResult', [awbHome::class, 'RsQuizSummaryResult']);
        Route::post('ListskillfuturebyMenuId', [awbHome::class, 'ListskillfuturebyMenuId']);
        Route::post('SubmitShareArticle', [awbHome::class, 'SubmitShareArticle']);
        Route::post('addPoint', [awbHome::class, 'addPoint']);
        Route::post('readLastAddPointWeMissYou', [awbHome::class, 'readLastAddPointWeMissYou']);
        Route::post('trackThis', [awbHome::class, 'trackThis']);
        Route::post('listArticleContentForYou', [awbHome::class, 'listArticleContentForYou']);
        Route::post('GetIqozUrl', [awbHome::class, 'GetIqozUrl']);
        Route::post('HomeCombo', [awbHome::class, 'HomeCombo']);
    });
    Route::prefix('awbProfile')->group(function () {
        Route::post('RsProfileTeam', [awbProfile::class, 'RsProfileTeam']);
        Route::post('RsProfileCourseAttended', [awbProfile::class, 'RsProfileCourseAttended']);
        Route::post('RsProfilePointHistory', [awbProfile::class, 'RsProfilePointHistory']);
        Route::post('RsProfileMostViewedTopic', [awbProfile::class, 'RsProfileMostViewedTopic']);
        Route::post('RsProfileContentViewed', [awbProfile::class, 'RsProfileContentViewed']);
        Route::post('RsProfileBadgesAchieved', [awbProfile::class, 'RsProfileBadgesAchieved']);
        Route::post('RsProfileReferral', [awbProfile::class, 'RsProfileReferral']);
        Route::post('InsertCourseAttended', [awbProfile::class, 'InsertCourseAttended']);
        Route::post('SelectCourseDetail', [awbProfile::class, 'SelectCourseDetail']);
        Route::post('UpdateCourseAttended', [awbProfile::class, 'UpdateCourseAttended']);
        Route::post('DeleteCourseAttended', [awbProfile::class, 'DeleteCourseAttended']);
        Route::post('CheckClaimRedeemCode', [awbProfile::class, 'CheckClaimRedeemCode']);
        Route::post('UpdateNotifierStatus', [awbProfile::class, 'UpdateNotifierStatus']);
        Route::post('GetUserProfile', [awbProfile::class, 'GetUserProfile']);
        Route::post('ServicesProfilesBadges', [awbProfile::class, 'ServicesProfilesBadges']);
        Route::post('readLeaderboard', [awbProfile::class, 'readLeaderboard']);
        Route::post('listPreferredTopic', [awbProfile::class, 'listPreferredTopic']);
        Route::post('createPreferredTopic', [awbProfile::class, 'createPreferredTopic']);
        Route::post('readPreferredTopic', [awbProfile::class, 'readPreferredTopic']);
        Route::post('deletePreferredTopic', [awbProfile::class, 'deletePreferredTopic']);
        Route::post('ListTerms', [awbProfile::class, 'ListTerms']);
    });
    Route::prefix('awbWorkshopSharingUser')->group(function () {
        Route::post('selectDataByUserAndWorkshopId', [awbWorkshopSharingUser::class, 'selectDataByUserAndWorkshopId']);
        Route::post('cekCountUserInWorkshopSharing', [awbWorkshopSharingUser::class, 'cekCountUserInWorkshopSharing']);
        Route::post('InsertData', [awbWorkshopSharingUser::class, 'InsertData']);
        Route::post('ListData', [awbWorkshopSharingUser::class, 'ListData']);
    });
    Route::prefix('awbArticleImport')->group(function () {
        Route::post('ListData', [awbArticleImport::class, 'ListData']);
        Route::post('ResetData', [awbArticleImport::class, 'ResetData']);
        Route::post('ImportData', [awbArticleImport::class, 'ImportData']);
    });

    Route::prefix('awbActivityLog')->group(function () {
        Route::post('ListData', [awbActivityLog::class, 'ListData']);
        Route::post('ExportData', [awbActivityLog::class, 'ExportData']);
    });

    Route::prefix('awbAnsweredQuiz')->group(function () {
        Route::post('ListData', [awbAnsweredQuiz::class, 'ListData']);
        Route::post('DeleteData', [awbAnsweredQuiz::class, 'DeleteData']);
        Route::post('ExportData', [awbAnsweredQuiz::class, 'ExportData']);
    });

    Route::prefix('awbEmailSubscribe')->group(function () {
        Route::post('ListData', [awbEmailSubscribe::class, 'ListData']);
        Route::post('DeleteData', [awbEmailSubscribe::class, 'DeleteData']);
        Route::post('Unsubscribe', [awbEmailSubscribe::class, 'Unsubscribe']);
        Route::post('ExportData', [awbEmailSubscribe::class, 'ExportData']);
    });

    Route::prefix('awbRedeemReward')->group(function () {
        Route::post('ListData', [awbRedeemReward::class, 'ListData']);
        Route::post('ExportData', [awbRedeemReward::class, 'ExportData']);
    });

    Route::prefix('awbRegisterCourse')->group(function () {
        Route::post('ListData', [awbRegisterCourse::class, 'ListData']);
        Route::post('ExportData', [awbRegisterCourse::class, 'ExportData']);
    });

    Route::prefix('awbShareArticle')->group(function () {
        Route::post('ListData', [awbShareArticle::class, 'ListData']);
        Route::post('ExportData', [awbShareArticle::class, 'ExportData']);
    });

    Route::prefix('awbSubmittedIdea')->group(function () {
        Route::post('ListData', [awbSubmittedIdea::class, 'ListData']);
        Route::post('DeleteData', [awbSubmittedIdea::class, 'DeleteData']);
        Route::post('ExportData', [awbSubmittedIdea::class, 'ExportData']);
    });
    Route::prefix('awbUserInfo')->group(function () {
        Route::post('ListData', [awbUserInfo::class, 'ListData']);
        Route::post('ImportData', [awbUserInfo::class, 'ImportData']);
        Route::post('ExportData', [awbUserInfo::class, 'ExportData']);
    });
    Route::prefix('awbQuiz')->group(function () {
        Route::post('ListData', [awbQuiz::class, 'ListData']);
        Route::post('InsertData', [awbQuiz::class, 'InsertData']);
        Route::post('UpdateData', [awbQuiz::class, 'UpdateData']);
        Route::post('SelectData', [awbQuiz::class, 'SelectData']);
        Route::post('DeleteData', [awbQuiz::class, 'DeleteData']);
        Route::post('SelectArticle', [awbQuiz::class, 'SelectArticle']);
    });
    Route::prefix('awbViewCourse')->group(function () {
        Route::post('getCategory', [awbViewCourse::class, 'getCategory']);
        Route::post('getSlider', [awbViewCourse::class, 'getSlider']);
        Route::post('getActivePeriod', [awbViewCourse::class, 'getActivePeriod']);
        Route::post('ListCourse', [awbViewCourse::class, 'ListCourse']);
        Route::post('getCourseDetail', [awbViewCourse::class, 'getCourseDetail']);
        Route::post('validateShareCourse', [awbViewCourse::class, 'validateShareCourse']);
        Route::post('SubmitShareCourse', [awbViewCourse::class, 'SubmitShareCourse']);
        Route::post('registerCourse', [awbViewCourse::class, 'registerCourse']);
    });

});

// Find Talent
Route::post('findTalentlogin', [findTalentUser::class, 'login']);
Route::post('findTalentlogout', [findTalentUser::class, 'logout_now']);
Route::post('findTalentrefreshToken', [findTalentUser::class, 'refreshToken']);

Route::middleware('jwt.verify')->group(function () {
    Route::prefix('findTalentUser')->group(function () {
        Route::get('user', [findTalentUser::class, 'getAuthenticatedUser']);
        Route::post('UpdateData', [findTalentUser::class, 'UpdateData']);
        Route::post('AddData', [findTalentUser::class, 'AddData']);
        Route::post('ValidateId', [findTalentUser::class, 'ValidateId']);
        Route::post('ListData', [findTalentUser::class, 'ListData']);
        Route::post('SelectData', [findTalentUser::class, 'SelectData']);
        Route::post('ActivityLog', [findTalentUser::class, 'ActivityLog']);
        Route::post('UpdatePhotos', [findTalentUser::class, 'UpdatePhotos']);
    });
    Route::prefix('findTalentPlatform')->group(function () {
        Route::post('ListData', [findTalentPlatform::class, 'ListData']);
        Route::post('SelectData', [findTalentPlatform::class, 'SelectData']);
        Route::post('SelectDataMD5', [findTalentPlatform::class, 'SelectDataMD5']);
        Route::post('InsertData', [findTalentPlatform::class, 'InsertData']);
        Route::post('UpdateData', [findTalentPlatform::class, 'UpdateData']);
        Route::post('ValidateId', [findTalentPlatform::class, 'ValidateId']);
        Route::post('ValidateHashtag', [findTalentPlatform::class, 'ValidateHashtag']);
        Route::get('GetAllCountry', [findTalentPlatform::class, 'GetAllCountry']);
        Route::get('GetAllFunction', [findTalentPlatform::class, 'GetAllFunction']);
        Route::post('GetAllEmployee', [findTalentPlatform::class, 'GetAllEmployee']);
        Route::post('GetAllEmployeeByCountry', [findTalentPlatform::class, 'GetAllEmployeeByCountry']);
        Route::post('DeleteData', [findTalentPlatform::class, 'DeleteData']);
        Route::post('GetPlatformAccess', [findTalentPlatform::class, 'GetPlatformAccess']);
        Route::post('getRoleAdmin', [findTalentPlatform::class, 'getRoleAdmin']);
    });
    Route::prefix('findTalentTheme')->group(function () {
        Route::post('ListData', [findTalentTheme::class, 'ListData']);
        Route::post('SelectData', [findTalentTheme::class, 'SelectData']);
        Route::post('InsertData', [findTalentTheme::class, 'InsertData']);
        Route::post('UpdateData', [findTalentTheme::class, 'UpdateData']);
        Route::post('ValidateId', [findTalentTheme::class, 'ValidateId']);
        Route::post('DeleteData', [findTalentTheme::class, 'DeleteData']);
        Route::post('SelectDataByPlatform', [findTalentTheme::class, 'SelectDataByPlatform']);
        Route::post('setAsDefault', [findTalentTheme::class, 'setAsDefault']);
    });
    Route::prefix('findTalentSlider')->group(function () {
        Route::post('ListData', [findTalentSlider::class, 'ListData']);
        Route::post('SelectData', [findTalentSlider::class, 'SelectData']);
        Route::post('InsertData', [findTalentSlider::class, 'InsertData']);
        Route::post('UpdateData', [findTalentSlider::class, 'UpdateData']);
        Route::post('ValidateId', [findTalentSlider::class, 'ValidateId']);
        Route::post('ValidateHashtag', [findTalentSlider::class, 'ValidateHashtag']);
        Route::post('DeleteData', [findTalentSlider::class, 'DeleteData']);
    });
    Route::prefix('findTalentReport')->group(function () {
        Route::post('ListDataUserProject', [findTalentReport::class, 'ListDataUserProject']);
        Route::post('SelectData', [findTalentReport::class, 'SelectData']);

        Route::post('ListDataReportSummary', [findTalentReport::class, 'ListDataReportSummary']);
        Route::post('FormExportReportSummary', [findTalentReport::class, 'FormExportReportSummary']);

        Route::post('ListDataReportDetail', [findTalentReport::class, 'ListDataReportDetail']);
        Route::post('FormExportReportDetail', [findTalentReport::class, 'FormExportReportDetail']);
    });
    Route::prefix('findTalentProject')->group(function () {
        Route::post('ListData', [findTalentProject::class, 'ListData']);
        Route::post('SelectData', [findTalentProject::class, 'SelectData']);
        Route::post('InsertData', [findTalentProject::class, 'InsertData']);
        Route::post('UpdateData', [findTalentProject::class, 'UpdateData']);
        Route::post('ValidateId', [findTalentProject::class, 'ValidateId']);
        Route::post('ValidateHashtag', [findTalentProject::class, 'ValidateHashtag']);
        Route::post('DeleteData', [findTalentProject::class, 'DeleteData']);
        Route::post('ListDataUserDirectorateByPlatform', [findTalentProject::class, 'ListDataUserDirectorateByPlatform']);
        Route::post('SelectDataParticipantByProjectId', [findTalentProject::class, 'SelectDataParticipantByProjectId']);
        Route::post('ListDataParticipantByProjectId', [findTalentProject::class, 'ListDataParticipantByProjectId']);
        Route::post('ListDataForHome', [findTalentProject::class, 'ListDataForHome']);
        Route::post('SubmitApplied', [findTalentProject::class, 'SubmitApplied']);
        Route::post('saveAsDraft', [findTalentProject::class, 'saveAsDraft']);
        Route::post('ListDataForUserByStatusProject', [findTalentProject::class, 'ListDataForUserByStatusProject']);
        Route::post('sendEmail', [findTalentProject::class, 'sendEmail']);
        Route::post('UpdateStatusProject', [findTalentProject::class, 'UpdateStatusProject']);
    });
    Route::prefix('findTalentQuestionnaire')->group(function () {
        Route::post('ListData', [findTalentQuestionnaire::class, 'ListData']);
        Route::post('SelectData', [findTalentQuestionnaire::class, 'SelectData']);
        Route::post('InsertData', [findTalentQuestionnaire::class, 'InsertData']);
        Route::post('UpdateData', [findTalentQuestionnaire::class, 'UpdateData']);
        Route::post('ValidateId', [findTalentQuestionnaire::class, 'ValidateId']);
        Route::post('ValidateHashtag', [findTalentQuestionnaire::class, 'ValidateHashtag']);
        Route::post('DeleteData', [findTalentQuestionnaire::class, 'DeleteData']);
        Route::post('ListDataForId', [findTalentQuestionnaire::class, 'ListDataForId']);
    });
    Route::prefix('findtalentActivityLog')->group(function () {
        Route::post('ListData', [findtalentActivityLog::class, 'ListData']);
        Route::post('FormExport', [findtalentActivityLog::class, 'FormExport']);
    });


});
