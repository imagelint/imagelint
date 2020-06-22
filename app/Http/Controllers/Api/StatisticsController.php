<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Stats\StatsEngine;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function daily(Request $request, StatsEngine $statsEngine) {
        $user = $request->user();
        return response()->json($statsEngine->daily($user->id, $request->input('start'), $request->input('end')));
    }
}
