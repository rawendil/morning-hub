<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\ReorderRoutineBlocksRequest;
use App\Http\Requests\MorningHub\StoreRoutineBlockRequest;
use App\Http\Requests\MorningHub\UpdateRoutineBlockRequest;
use App\Models\RoutineBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class RoutineBlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'blocks' => $user->routineBlocks()->ordered()->with('clickUpConnection')->get(),
            'connections' => $user->clickUpConnections()->get(),
            'googleCalendarConnectionId' => $user->googleCalendarConnection?->id,
        ]);
    }

    public function store(StoreRoutineBlockRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $nextSortOrder = $user->routineBlocks()->max('sort_order') + 1;

        $block = $user->routineBlocks()->create(
            array_merge($request->validated(), ['sort_order' => $nextSortOrder])
        );

        return response()->json(['block' => $block], 201);
    }

    public function update(UpdateRoutineBlockRequest $request, RoutineBlock $block): JsonResponse
    {
        Gate::authorize('update', $block);
        $block->update($request->validated());

        return response()->json(['block' => $block->fresh()]);
    }

    public function destroy(Request $request, RoutineBlock $block): Response
    {
        Gate::authorize('delete', $block);
        $block->delete();

        return response()->noContent();
    }

    public function reorder(ReorderRoutineBlocksRequest $request): Response
    {
        foreach ($request->validated('blocks') as $index => $blockId) {
            RoutineBlock::where('id', $blockId)->update(['sort_order' => $index]);
        }

        return response()->noContent();
    }
}
