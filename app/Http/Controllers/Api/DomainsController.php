<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class DomainsController extends Controller
{
    public function list(Request $request, Domain $domain) {
        $user = $request->user();
        return response()->json($domain->where('user_id', $user->id)->get());
    }
    public function add(Request $request) {
        $user = $request->user();
        $response = [];
        try {
            $domain = new Domain();
            $domain->user_id = $user->id;
            $domain->domain = trim(strtolower($request->input('domain')));
            $domain->account = 'a1.imagelint.test';
            $domain->save();
            $response['success'] = 1;
            // $response['message'] = 'The domain is successfully added';
        } catch (QueryException $e){
            $errorCode = $e->errorInfo[1];
            $response['success'] = 0;
            if($errorCode == 1062)
                $response['message'] = 'The domain is exist';
            else
                $response['message'] = 'Something is wrong';
        }
        return response()->json($response);
    }
}
