<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly ResetUserPassword $resetUserPassword,
    ) {}

    public function __invoke(Request $request): Response
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $this->resetUserPassword->reset($user, ['password' => $password, 'password_confirmation' => $password]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }

        return response()->noContent();
    }
}
