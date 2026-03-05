<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('morning-hub/Guide');
    }
}
