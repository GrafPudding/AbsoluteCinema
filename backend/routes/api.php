<?php

use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Ticket;

const PRESENCE_CAPACITY = 3;
const PRESENCE_TTL_SECONDS = 25;

function seat_codes(): array {
    $rows = range('A', 'H'); // 8 rows
    $cols = range(1, 10);    // 10 seats each
    $out = [];
    foreach ($rows as $r) {
        foreach ($cols as $c) {
            $out[] = $r . $c;
        }
    }
    return $out;
}

function presence_key(int $showtimeId): string {
    return "presence:{$showtimeId}";
}

function presence_member(int $showtimeId, string $token): string {
    return "{$showtimeId}:{$token}";
}

function client_token(Request $request): string {
    // Prefer header, fall back to cookie
    return (string) ($request->header('X-Client-Token') ?: $request->cookie('cinema_client') ?: '');
}

function reserve_key(int $showtimeId, string $seatCode): string {
    return "reserve:{$showtimeId}:{$seatCode}";
}


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['ok' => true]);
});

Route::get('/movies', function () {
    return Movie::query()->get();
});

Route::post('/showtimes/{showtime}/enter', function (Showtime $showtime, Request $request) {
    $token = client_token($request);
    if ($token === '') return response()->json(['message' => 'Missing client token'], 400);

    $key = presence_key($showtime->id);
    $member = presence_member($showtime->id, $token);
    $now = time();

    // remove stale
    Redis::zremrangebyscore($key, '-inf', $now - PRESENCE_TTL_SECONDS);

    // add/update presence
    Redis::zadd($key, $now, $member);
    Redis::expire($key, PRESENCE_TTL_SECONDS + 10);

    // rank (0-based)
    $rank = Redis::zrank($key, $member);
    $allowed = ($rank !== null && $rank < PRESENCE_CAPACITY);

    return response()->json([
        'ok' => true,
        'allowed' => $allowed,
        'position' => $rank === null ? null : ($rank + 1),
        'capacity' => PRESENCE_CAPACITY,
        'ttl_seconds' => PRESENCE_TTL_SECONDS,
    ]);
});

Route::post('/showtimes/{showtime}/heartbeat', function (Showtime $showtime, Request $request) {
    $token = client_token($request);
    if ($token === '') return response()->json(['message' => 'Missing client token'], 400);

    $key = presence_key($showtime->id);
    $member = presence_member($showtime->id, $token);
    $now = time();

    Redis::zadd($key, $now, $member);
    Redis::expire($key, PRESENCE_TTL_SECONDS + 10);

    return response()->json(['ok' => true]);
});

Route::post('/showtimes/{showtime}/leave', function (Showtime $showtime, Request $request) {
    $token = client_token($request);
    if ($token === '') return response()->json(['message' => 'Missing client token'], 400);

    $key = presence_key($showtime->id);
    $member = presence_member($showtime->id, $token);

    Redis::zrem($key, $member);

    return response()->json(['ok' => true]);
});

Route::get('/movies/{movie}/showtimes', function (\App\Models\Movie $movie) {
    return Showtime::query()
        ->where('movie_id', $movie->id)
        ->orderBy('starts_at')
        ->get(['id', 'movie_id', 'starts_at', 'auditorium']);
});

Route::get('/showtimes/{showtime}/seats', function (Showtime $showtime, Request $request) {
    $token = client_token($request);

    $allSeats = seat_codes();

    // Bought seats from DB
    $bought = Ticket::query()
        ->where('showtime_id', $showtime->id)
        ->pluck('seat_code')
        ->all();
    $boughtSet = array_fill_keys($bought, true);

    // Reserved seats from Redis
    $reservedBy = []; // seat_code => token
    foreach ($allSeats as $seat) {
        $val = Redis::get(reserve_key($showtime->id, $seat));
        if ($val) $reservedBy[$seat] = $val;
    }

    $seats = [];
    foreach ($allSeats as $seat) {
        if (isset($boughtSet[$seat])) {
            $seats[] = ['seat' => $seat, 'status' => 'bought', 'reservedByMe' => false];
            continue;
        }

        if (isset($reservedBy[$seat])) {
            $seats[] = [
                'seat' => $seat,
                'status' => 'reserved',
                'reservedByMe' => ($token !== '' && $reservedBy[$seat] === $token),
            ];
            continue;
        }

        $seats[] = ['seat' => $seat, 'status' => 'available', 'reservedByMe' => false];
    }

    return response()->json([
        'showtime' => [
            'id' => $showtime->id,
            'movie_id' => $showtime->movie_id,
            'starts_at' => $showtime->starts_at,
            'auditorium' => $showtime->auditorium,
        ],
        'layout' => ['rows' => 8, 'cols' => 10],
        'seats' => $seats
    ]);
});

Route::post('/showtimes/{showtime}/reserve', function (Showtime $showtime, Request $request) {
    $token = client_token($request);
    if ($token === '') {
        return response()->json(['message' => 'Missing client token'], 400);
    }

    $seats = $request->input('seats', []);
    if (!is_array($seats) || count($seats) < 1 || count($seats) > 5) {
        return response()->json(['message' => 'Select 1 to 5 seats'], 422);
    }

    $valid = array_fill_keys(seat_codes(), true);
    foreach ($seats as $s) {
        if (!is_string($s) || !isset($valid[$s])) {
            return response()->json(['message' => "Invalid seat: {$s}"], 422);
        }
    }

    // Check already bought
    $boughtCount = Ticket::query()
        ->where('showtime_id', $showtime->id)
        ->whereIn('seat_code', $seats)
        ->count();

    if ($boughtCount > 0) {
        return response()->json(['message' => 'One or more seats already bought'], 409);
    }

    // Check reserved by others
    foreach ($seats as $seat) {
        $key = reserve_key($showtime->id, $seat);
        $existing = Redis::get($key);
        if ($existing && $existing !== $token) {
            return response()->json(['message' => "Seat {$seat} is reserved"], 409);
        }
    }

    // Reserve with TTL (10 minutes)
    $ttlSeconds = 10 * 60;
    foreach ($seats as $seat) {
        $key = reserve_key($showtime->id, $seat);
        Redis::setex($key, $ttlSeconds, $token);
    }

    return response()->json(['ok' => true, 'reserved' => $seats, 'ttl_seconds' => $ttlSeconds]);
});


Route::post('/showtimes/{showtime}/buy', function (Showtime $showtime, Request $request) {
    $token = client_token($request);
    if ($token === '') {
        return response()->json(['message' => 'Missing client token'], 400);
    }

    $seats = $request->input('seats', []);
    if (!is_array($seats) || count($seats) < 1 || count($seats) > 5) {
        return response()->json(['message' => 'Select 1 to 5 seats'], 422);
    }

    // Must be reserved by this user
    foreach ($seats as $seat) {
        $key = reserve_key($showtime->id, $seat);
        $existing = Redis::get($key);
        if (!$existing) {
            return response()->json(['message' => "Seat {$seat} is not reserved"], 409);
        }
        if ($existing !== $token) {
            return response()->json(['message' => "Seat {$seat} reserved by someone else"], 409);
        }
    }

    // Write to DB (unique constraint prevents duplicates)
    foreach ($seats as $seat) {
        Ticket::query()->create([
            'showtime_id' => $showtime->id,
            'seat_code' => $seat,
        ]);
        Redis::del(reserve_key($showtime->id, $seat));
    }

    return response()->json(['ok' => true, 'bought' => $seats]);
});


Route::post('/showtimes/{showtime}/cancel', function (Showtime $showtime, Request $request) {
    $token = client_token($request);
    if ($token === '') {
        return response()->json(['message' => 'Missing client token'], 400);
    }

    $seats = $request->input('seats', []);
    if (!is_array($seats) || count($seats) < 1 || count($seats) > 5) {
        return response()->json(['message' => 'Select 1 to 5 seats'], 422);
    }

    foreach ($seats as $seat) {
        $key = reserve_key($showtime->id, $seat);
        $existing = Redis::get($key);
        if ($existing === $token) {
            Redis::del($key);
        }
    }

    return response()->json(['ok' => true]);
});
