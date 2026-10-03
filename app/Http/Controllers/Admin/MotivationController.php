<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MotivationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MotivationRequest;
use App\Models\Motivation;
use App\Services\MotivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MotivationController extends Controller
{
    public function __construct(private readonly MotivationService $motivations) {}

    public function index(): View
    {
        return view('admin.motivations.index', [
            'motivations' => Motivation::latest('publish_date')->latest('id')->paginate(20),
            'today' => $this->motivations->today(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Motivation(['media_type' => MotivationType::TIP, 'is_active' => true]));
    }

    public function store(MotivationRequest $request): RedirectResponse
    {
        $this->motivations->save(null, $request->validated(), $request->file('image'), false, $request->user());

        return to_route('admin.motivations.index')->with('success', 'تمت الإضافة.');
    }

    public function edit(Motivation $motivation): View
    {
        return $this->form($motivation);
    }

    public function update(MotivationRequest $request, Motivation $motivation): RedirectResponse
    {
        $this->motivations->save($motivation, $request->validated(), $request->file('image'), $request->boolean('remove_image'), $request->user());

        return to_route('admin.motivations.index')->with('success', 'تم الحفظ.');
    }

    public function destroy(Motivation $motivation): RedirectResponse
    {
        $this->motivations->delete($motivation);

        return to_route('admin.motivations.index')->with('success', 'تم الحذف.');
    }

    private function form(Motivation $motivation): View
    {
        return view('admin.motivations.form', ['motivation' => $motivation, 'types' => MotivationType::cases()]);
    }
}
