<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\SetArticleReadRequest;
use App\Models\User;
use App\Services\ReadArticleService;
use Illuminate\Http\JsonResponse;

class ReadArticleController extends Controller
{
    public function __construct(
        private readonly ReadArticleService $readArticleService,
    ) {}

    public function store(SetArticleReadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->readArticleService->setRead(
            $user,
            $request->validated('link'),
            $request->boolean('read'),
        );

        return response()->json($this->readArticleService->readLinksFor($user));
    }
}
