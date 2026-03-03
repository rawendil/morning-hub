<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Models\RoutineBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HabitToggleController extends Controller
{
    public function __invoke(Request $request, RoutineBlock $block): JsonResponse
    {
        Gate::authorize('update', $block);

        $request->validate([
            'index' => ['required', 'integer', 'min:0'],
        ]);

        $index = $request->integer('index');
        $habits = $block->config['habits'] ?? [];

        if ($index >= count($habits)) {
            abort(422, 'Invalid habit index.');
        }

        $sessionKey = "habits_block_{$block->id}";
        $state = $request->session()->get($sessionKey, []);
        $today = now()->toDateString();

        if (($state['date'] ?? null) !== $today) {
            $state = ['date' => $today, 'completed' => []];
        }

        $completed = $state['completed'];

        if (in_array($index, $completed, true)) {
            $completed = array_values(array_diff($completed, [$index]));
        } else {
            $completed[] = $index;
        }

        $state['completed'] = $completed;
        $request->session()->put($sessionKey, $state);

        return response()->json(['completed' => $completed]);
    }
}
