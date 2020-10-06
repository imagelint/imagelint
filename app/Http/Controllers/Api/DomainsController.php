<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Repositories\DomainRepository;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DomainsController extends Controller
{
    public function list(Request $request)
    {
        $user = $request->user();
        $domains = Domain::where('user_id', $user->id)->get()->transform(function ($domain) {
            return $domain->only(['id', 'domain', 'created_at']);
        });
        return response()->json($domains);
    }

    public function add(Request $request)
    {
        $user = $request->user();
        $response = [];
        $inputDomain = trim(strtolower($request->input('domain')));
        if (DomainRepository::isDomainExists($user->id, $inputDomain))
        {
            app()->abort(400, 'This domain exists already');
        }
        DB::transaction(function () use ($inputDomain, $user) {
            $domain = new Domain();
            $domain->user_id = $user->id;
            $domain->domain = $inputDomain;
            $domain->account = 'a1.imagelint.test';
            $domain->save();
        }, 5);
        $response['success'] = 1;
        return response()->json($response);
    }
}
