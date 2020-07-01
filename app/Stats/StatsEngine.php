<?php

namespace App\Stats;

use App\Access;
use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsEngine {
    public function bytesSavedPerDay($days = 30) {
        $now = Carbon::now();
        $data = [];

        while($now > Carbon::now()->subDays($days)) {
            $entries = Access
                ::where('user_id', user()->id)
                ->whereBetween('created_at',[$now->format('Y-m-d 00:00:00'),$now->format('Y-m-d 23:59:59')])
                ->select(DB::raw('size'), DB::raw('COUNT(*) as count'), 'original_id')
                ->groupBy('original_id', 'size')
                ->with('original')
                ->get();
            $saved = 0;
            foreach($entries as $entry) {
                $saved += ($entry->original->size * $entry->count) - $entry->size;
            }
            $data[$now->format('Y-m-d')] = $saved;
            $now->subDay();
        }
        return array_reverse($data);
    }

    public function imagesServedPerDay($days = 30) {
        $now = Carbon::now();
        $data = [];

        while($now > Carbon::now()->subDays($days)) {
            $count = Access
                ::where('user_id', user()->id)
                ->whereBetween('created_at',[$now->format('Y-m-d 00:00:00'),$now->format('Y-m-d 23:59:59')])
                ->where('id','<',10000)
                ->count();
            $data[$now->format('Y-m-d')] = $count;
            $now->subDay();
        }

        return array_reverse($data);
    }

    public function daily($userId, $start, $end) {
        $start = Carbon::parse($start);
        $now = Carbon::parse($start);
        $end = Carbon::parse($end);
        $data = [];
        $accounts = Domain::where('user_id', $userId)->groupBy('account')->pluck('account');

        while($now <= $end) {
            $tableName = 'access_logs_' . $now->format('Ymd');
            try {
                $day = DB::table($tableName)
                    ->whereIn('account', $accounts)
                    ->whereBetween($tableName . '.created_at',[$now->format('Y-m-d 00:00:00'),$now->format('Y-m-d 23:59:59')])
                    ->select(DB::raw('SUM(size) as size'), DB::raw('COUNT(*) as count'))
                    ->first();
                $dayData = [
                    "traffic" => $day->size,
                    "count" => $day->count,
                ];
            } catch (\Exception $e) {
                $dayData = [
                    'traffic' => 0,
                    'count' => 0,
                ];
            }
            $dayData['date'] = $now->format('Y-m-d');
            $data[] = $dayData;
            $now->addDay();
        }
        return [
            'days' => $data,
            'originals' => 0, // TODO calculate originals
        ];
    }
}
