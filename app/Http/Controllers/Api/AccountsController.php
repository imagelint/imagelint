<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    public function mine(Request $request)
    {
        $user = $request->user();
        if(!$user->account) {
            app()->abort(400, 'This account doesn\'t exist');
        }
        $account = $user->account->only(['id', 'account']);
        return response()->json($account);
    }
}
