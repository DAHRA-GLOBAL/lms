<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Requests\StoreLessonRequest;
use App\Models\Lesson;
use Jambasangsang\Flash\Facades\LaravelFlash;
use Peopleaps\Scorm\Manager\ScormManager;
use Peopleaps\Scorm\Model\ScormModel;


class LessonController extends Controller
{

    /** @var ScormManager */
    private $scormManager;

    /**
     * ScormController constructor.
     */
    public function __construct( ScormManager $scormManager)
    {
        $this->scormManager = $scormManager;
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
        $scormModel->resource_type = 'lesson';
        $scormModel->resource_id = $request->id;

        dd($scormModel);
        $lesson = Lesson::create($request->validated());
        $lesson->image  = uploadOrUpdateFile($request, $lesson->image, \constPath::LessonImage);
        $lesson->save();

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
        return view('jambasangsang.backend.lessons.show', ['course' => Course::with('teacher:id,name,email', 'category:id,name', 'lessons')->whereSlug($slug)->first()]);
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
