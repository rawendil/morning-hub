<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TimezoneUpdateRequest;
use App\Models\User;
use Illuminate\Http\Response;

class TimezoneController extends Controller
{
    public function update(TimezoneUpdateRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['timezone' => $request->validated('timezone')])->save();

        return response()->noContent();
    }
}
