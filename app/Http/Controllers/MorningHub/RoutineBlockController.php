<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\ReorderRoutineBlocksRequest;
use App\Http\Requests\MorningHub\StoreRoutineBlockRequest;
use App\Http\Requests\MorningHub\UpdateRoutineBlockRequest;
use App\Models\RoutineBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoutineBlockController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('morning-hub/Routine', [
            'blocks' => $request->user()->routineBlocks()->ordered()->with('clickUpConnection')->get(),
            'connections' => $request->user()->clickUpConnections()->get(),
        ]);
    }

    public function store(StoreRoutineBlockRequest $request): RedirectResponse
    {
        $nextSortOrder = $request->user()->routineBlocks()->max('sort_order') + 1;

        $request->user()->routineBlocks()->create(
            array_merge($request->validated(), ['sort_order' => $nextSortOrder])
        );

        return to_route('morning-hub.routine.index');
    }

    public function update(UpdateRoutineBlockRequest $request, RoutineBlock $block): RedirectResponse
    {
        $block->update($request->validated());

        return to_route('morning-hub.routine.index');
    }

    public function destroy(Request $request, RoutineBlock $block): RedirectResponse
    {
        Gate::authorize('delete', $block);

        $block->delete();

        return to_route('morning-hub.routine.index');
    }

    public function reorder(ReorderRoutineBlocksRequest $request): RedirectResponse
    {
        foreach ($request->validated('blocks') as $index => $blockId) {
            RoutineBlock::where('id', $blockId)->update(['sort_order' => $index]);
        }

        return to_route('morning-hub.routine.index');
    }
}
