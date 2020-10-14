<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Repositories\DomainRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DomainsController extends Controller
{
    public function list(Request $request)
    {
        $user = $request->user();
        $domains = Domain::where('account_id', $user->account_id)->get()->transform(function ($domain) {
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
            if ($domainRepository->domainExists($user->account_id, $inputDomain)) {
                app()->abort(400, 'This domain exists already');
            }
            $domain = new Domain();
            $domain->domain = $inputDomain;
            $domain->account_id = $user->account_id;
            $domain->save();
        }, 5);
        $response['success'] = 1;
        return response()->json($response);
    }
    public function checkIfExist(Request $request, DomainRepository $domainRepository)
    {
        $user = $request->user();
        if (!$domainRepository->domainExists($user->account_id, trim(strtolower($request->input('domain'))))) {
            app()->abort(400, 'This domain doesn\'t exist');
        }
        return response()->json(['success'=>true]);
    }
}
