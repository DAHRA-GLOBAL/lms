<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\Contract\ScormTrackServiceContract;
use App\Strategies\ScormFieldStrategy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\StoreLessonRequest;
use App\Models\Lesson;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jambasangsang\Flash\Facades\LaravelFlash;
use Peopleaps\Scorm\Manager\ScormManager;
use Peopleaps\Scorm\Model\ScormModel;
use Peopleaps\Scorm\Model\ScormScoModel;
use Peopleaps\Scorm\Model\ScormScoTrackingModel;


class LessonController extends Controller
{

    /** @var ScormManager */
    private $scormManager;

    private ScormTrackServiceContract $scormTrackService;
    /**
     * ScormController constructor.
     */
    public function __construct( ScormManager $scormManager, ScormTrackServiceContract $scormTrackService)
    {
        $this->scormManager = $scormManager;
        $this->scormTrackService = $scormTrackService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lessons = Lesson::all();
        foreach ($lessons as $lesson) {
            $lesson->image = asset($lesson->image);
        }
        return $lesson;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($slug)
    {
        return view('jambasangsang.backend.lessons.create', ['course' => Course::whereSlug($slug)->first()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLessonRequest $request)
    {

        $uploadedFile = $request->file('image');
        $scormModel = $this->scormManager->uploadScormArchive($uploadedFile);
        $scormModel->resource_type = 'App\Models\User';
        $scormModel->resource_id = auth()->user()->id;

        $lesson = Lesson::create($request->validated());
        $lesson->image  = uploadOrUpdateFile($request, $lesson->image, \constPath::LessonImage);
        $lesson->save();

        $this->scormTrackService->updateScoTracking($scormModel->uuid, auth()->user()->id, ['cmi.core.lesson_status' => 'incomplete']);


        LaravelFlash::withSuccess('Lesson Created Successfully');
        return redirect()->route('courses.show', [$request->slug]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $courses = Course::with('lessons', 'teacher:id,name,email','category:id,name')->whereSlug($slug)->first();
        return view('jambasangsang.backend.lessons.show', ['course' => $courses]);
    }
    public function play(string $uuid, Request $request): View
    {
        $data = $this->getScoViewDataByUuid(
            $uuid,
            $request->user() ? $request->user()->getKey() : null,
            $request->bearerToken()
        );
        return view('scorm.player', ['data' => $data]);

    }

    public function sendResponse($data, string $message = '', int $code = 200): JsonResponse
    {
        $body = [
            'success' => $code >= 200 && $code < 300,
            'message' => $message,
        ];
        if (!is_null($data)) {
            $body['data'] = $data;
        }
        return Response::json($body, $code);
    }

    public function sendError(string $error = '', int $code = 404): JsonResponse
    {
        return $this->sendResponse(null, $error, $code);
    }

    public function sendSuccess(string $message = '', int $code = 200): JsonResponse
    {
        return $this->sendResponse(null, $message, $code);
    }

    public function set(Request $request, string $uuid): JsonResponse
    {
        $this->scormTrackService->updateScoTracking(
            $uuid,
            $request->user()->getKey(),
            $request->input('cmi')
        );

        return $this->sendSuccess();
    }

    public function get(Request $request, int $scoId, string $key): JsonResponse
    {
        $data = $this->scormTrackService->getUserResultSpecifiedValue($key, $scoId, $request->user()->getKey());
        return new JsonResponse($data);
    }

    public function getScoViewDataByUuid(string $scoUuid, int $userId = null, string $token = null): ScormScoModel
    {
        $data = $this->getScoByUuid($scoUuid);

        return $this->getScormPlayerConfig($data, $userId, $token);
    }

    private function getScormPlayerConfig(ScormScoModel $data, int $userId = null, string $token = null): ScormScoModel
    {
        $cmi = $this->getScormTrackData($data, $userId);
        $data['entry_url_absolute'] = Storage::disk(config('scorm.disk'))
            ->url('/109fd072-7f03-4090-ab6e-782ce7fc1c4d/index_scorm.html'.$data->sco_parameters);
        $data['version'] = $data->scorm->version;
        $data['token'] = $token;
        $data['lmsUrl'] = url('scorm/track');
        $data['player'] = (object) [
            'autoCommit' => (bool) $token,
            'lmsCommitUrl' => $token ? url('scorm/track', $data->uuid) : false,
            'xhrHeaders' => [
                'Authorization' => $token ? ('Bearer '.$token) : null,
            ],
            'logLevel' => 1,
            'autoProgress' => (bool) $token,
            'cmi' => $cmi,
        ];

        return $data;
    }
    private function getScormTrackData(ScormScoModel $data, ?int $userId): array
    {
        return $this
            ->getScormFieldStrategy($data->scorm->version)
            ->getCmiData(
                $this->getScormTrack($data->getKey(), $userId)
            );
    }
    private function getScormTrack(int $scoId, ?int $userId): ?ScormScoTrackingModel
    {
        if (is_null($userId)) {
            return null;
        }

        return $this->scormTrackService->getUserResult($scoId, $userId);
    }

    private function getScormFieldStrategy(string $version): ScormFieldStrategy
    {
        $scormVersion = Str::ucfirst(Str::camel($version));
        $strategy = 'App\\Strategies\\'.$scormVersion.'FieldStrategy';

        return new ScormFieldStrategy(new $strategy());
    }

    public function getScoByUuid($scoUuid)
    {
        return ScormScoModel::with('scorm')
            ->where('uuid',$scoUuid)
            ->firstOrCreate();

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
