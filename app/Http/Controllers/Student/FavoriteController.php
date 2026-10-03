<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function __invoke(Request $request, StudentProgressService $progress): View
    {
        $contents = Content::visible()
            ->whereIn('id', fn ($q) => $q->select('content_id')->from('favorites')->where('student_id', $request->user()->id))
            ->with('source')->ordered()->get();

        return view('student.favorites', [
            'contents' => $contents,
            'statuses' => $progress->statuses($request->user(), $contents->pluck('id')),
        ]);
    }
}
