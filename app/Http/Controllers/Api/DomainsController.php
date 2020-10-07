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

    public function add(Request $request, DomainRepository $domainRepository)
    {
        $user = $request->user();
        $response = [];
        $inputDomain = trim(strtolower($request->input('domain')));
        DB::transaction(function () use ($inputDomain, $user, $domainRepository) {
            if ($domainRepository->domainExists($user->id, $inputDomain)) {
                app()->abort(400, 'This domain exists already');
            }
            $domain = new Domain();
            $domain->user_id = $user->id;
            $domain->domain = $inputDomain;
            $domain->account = 'a1.imagelint.test';
            $domain->save();
        }, 5);
        $response['success'] = 1;
        return response()->json($response);
    }
    public function checkIfExist(Request $request)
    {
        $user = $request->user();
        if (!DomainRepository::isDomainExists($user->id, trim(strtolower($request->input('domain'))))) {
            app()->abort(400, 'This domain doesn\'t exist');
        }
        return response()->json(['success'=>true]);
    }
}
