<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;

class DomainsController extends Controller
{
    public function list(Request $request, Domain $domain) {
        $user = $request->user();
        //return response()->json($user);
        return response()->json($domain->where('user_id', $user->id)->get());
    }
}
