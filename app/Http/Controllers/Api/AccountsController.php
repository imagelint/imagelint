<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    public function one(Request $request)
    {
        $user = $request->user();
        if(empty($user->account)) {
            app()->abort(400, 'This account doesn\'t exist');
        }
        $account = $user->account;
        return response()->json($account);
    }
}
