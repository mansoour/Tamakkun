<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\AccountActivation;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class UserStatusController extends Controller
{
    public function __invoke(UpdateUserStatusRequest $request, User $user, AccountActivation $activation): RedirectResponse
    {
        try {
            $activation->changeStatus($user, UserStatus::from($request->validated('status')));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['account_status' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تحديث حالة الحساب.');
    }
}
