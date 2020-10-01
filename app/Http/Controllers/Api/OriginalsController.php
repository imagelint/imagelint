<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Original;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class OriginalsController extends Controller
{
    public function update(Request $request, $original_id) {
        $user = $request->user();
        /** @var Original $original */
        $original = Original::find($original_id);
        if($original->user_id !== $user->id) {
            return app()->abort(403);
        }
        $original->quality = $request->get('quality', null) ?: null;
        $original->width = $request->get('width', null) ?: null;
        $original->height = $request->get('height', null) ?: null;
        $original->save();
        $original->clearCache();

        return Response::make('', 204);
    }

    public function list(Request $request) {
        $user = $request->user();
        return Response::json(Original::where('user_id', $user->id)->get());
    }
}
