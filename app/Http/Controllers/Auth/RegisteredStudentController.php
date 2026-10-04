<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Services\StudentRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Student self-registration, available while the `student_registration`
 * setting is not "closed" (see docs/security.md#registration).
 */
class RegisteredStudentController extends Controller
{
    public function __construct(private readonly StudentRegistrationService $registration) {}

    public function create(): View
    {
        abort_unless($this->registration->isOpen(), 404);

        return view('auth.register', [
            'schools' => $this->registration->options(),
            'needsApproval' => $this->registration->mode() === 'approval',
        ]);
    }

    public function store(RegisterStudentRequest $request): RedirectResponse
    {
        abort_unless($this->registration->isOpen(), 404);

        $profile = $this->registration->register($request->safe()->only(['name', 'username', 'email', 'password', 'classroom_id']));

        if (! $profile->user->status->canSignIn()) {
            return redirect()->route('login')
                ->with('status', 'تم إنشاء حسابك، وسيُفعَّل بعد مراجعة المدرسة، ثم يمكنك تسجيل الدخول.');
        }

        Auth::login($profile->user);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard')->with('success', 'أهلًا بكِ في المنصة! تم إنشاء حسابك بنجاح.');
    }
}
