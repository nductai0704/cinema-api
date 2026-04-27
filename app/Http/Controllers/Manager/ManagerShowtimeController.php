<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\Movie;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Resources\ManagerShowtimeResource;

class ManagerShowtimeController extends Controller
{
    public function index()
    {
        // Tự động lọc showtimes theo Cinema của Manager
        $showtimes = Showtime::with(['movie.genres', 'room.roomType', 'roomType'])
            ->orderBy('show_date')
            ->orderBy('start_time')
            ->paginate(20);
            
        return ManagerShowtimeResource::collection($showtimes);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'movie_id' => 'required|integer',
                'room_id' => 'required|integer',
                'show_date' => 'required|date_format:Y-m-d',
                'start_time' => 'required|date_format:H:i',
                'ticket_price' => 'required|numeric|min:0',
            ]);

            $movie = Movie::find($data['movie_id']);
            $room = Room::find($data['room_id']);
            if (!$movie || !$room) {
                return response()->json(['message' => 'Phim hoặc Phòng chiếu không tồn tại!'], 404);
            }

            // Tính toán end_time tự động
            $duration = (int) ($movie->duration ?? 0);
            $startTime = Carbon::parse($data['show_date'] . ' ' . $data['start_time']);
            $endTime = $startTime->copy()->addMinutes($duration);
            
            $data['start_time'] = $startTime->format('H:i:s');
            $data['end_time'] = $endTime->format('H:i:s');
            // Mặc định lấy loại phòng từ phòng nếu tạo đơn lẻ
            $data['room_type_id'] = $room->room_type_id;

            // Conflict Checker
            $cleaningTime = (int) config('cinema.cleaning_time_minutes', 15);
            $newStart = $startTime->copy();
            $newEnd = $endTime->copy()->addMinutes($cleaningTime);

            $conflicts = Showtime::where('room_id', $data['room_id'])
                ->where('show_date', $data['show_date'])
                ->where('status', 'active')
                ->get()
                ->filter(function ($exist) use ($newStart, $newEnd, $cleaningTime) {
                    $existStart = $exist->start_date_time;
                    $existEndWithClean = $exist->end_date_time->addMinutes($cleaningTime);
                    return ($newStart->lt($existEndWithClean) && $newEnd->gt($existStart));
                });

            if ($conflicts->count() > 0) {
                return response()->json([
                    'message' => 'Lịch chiếu bị trùng hoặc quá sát giờ nghỉ.',
                    'conflicts' => $conflicts
                ], 409);
            }

            $showtime = Showtime::create($data);

            return response()->json([
                'message' => 'Lên lịch suất chiếu thành công.',
                'data' => new ManagerShowtimeResource($showtime->load(['movie.genres', 'room.roomType', 'roomType']))
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $showtime = Showtime::with(['movie.genres', 'room.roomType', 'roomType'])->findOrFail($id);
        return new ManagerShowtimeResource($showtime);
    }

    /**
     * POST /api/v1/manager/showtimes/bulk
     * Tạo hàng loạt suất chiếu với kiểm tra xung đột thời gian.
     */
    public function bulkStore(Request $request)
    {
        try {
            $request->validate([
                'movie_id' => 'required|integer',
                'room_id' => 'required|integer',
                'show_date' => 'required|date_format:Y-m-d',
                'ticket_price' => 'required|numeric|min:0',
                'showtimes' => 'required|array|min:1',
                'showtimes.*.start_time' => 'required|date_format:H:i',
                'showtimes.*.end_time' => 'nullable|date_format:H:i',
                'showtimes.*.format' => 'nullable|string',
            ]);

            $movieId = $request->movie_id;
            $roomId = $request->room_id;
            $showDate = $request->show_date;
            $ticketPrice = $request->ticket_price;
            $bulkShowtimes = $request->showtimes;

            $movie = Movie::find($movieId);
            $room = Room::find($roomId);
            if (!$movie || !$room) {
                return response()->json(['message' => 'Phim hoặc Phòng chiếu không tồn tại.'], 404);
            }

            $roomTypes = \App\Models\RoomType::all()->pluck('room_type_id', 'name');
            $duration = (int) ($movie->duration ?? 0);
            $cleaningTime = (int) config('cinema.cleaning_time_minutes', 15);

            $newSessions = [];
            foreach ($bulkShowtimes as $idx => $st) {
                $start = Carbon::parse("$showDate {$st['start_time']}");
                if (empty($st['end_time'])) {
                    $end = $start->copy()->addMinutes($duration);
                } else {
                    $end = Carbon::parse("$showDate {$st['end_time']}");
                }

                $formatName = isset($st['format']) ? strtoupper($st['format']) : null;
                $roomTypeId = $formatName ? ($roomTypes[$formatName] ?? $room->room_type_id) : $room->room_type_id;

                $newSessions[] = [
                    'start' => $start,
                    'end' => $end,
                    'label' => "{$st['start_time']} - " . $end->format('H:i'),
                    'original_idx' => $idx,
                    'room_type_id' => $roomTypeId
                ];
            }

            // Kiểm tra xung đột nội bộ
            for ($i = 0; $i < count($newSessions); $i++) {
                for ($j = $i + 1; $j < count($newSessions); $j++) {
                    $a = $newSessions[$i]; $b = $newSessions[$j];
                    $aEndClean = $a['end']->copy()->addMinutes($cleaningTime);
                    $bEndClean = $b['end']->copy()->addMinutes($cleaningTime);
                    if ($a['start']->lt($bEndClean) && $aEndClean->gt($b['start'])) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Xung đột nội bộ giữa {$a['label']} và {$b['label']}."
                        ], 400);
                    }
                }
            }

            // Kiểm tra xung đột DB
            $existingShowtimes = Showtime::where('room_id', $roomId)
                ->where('show_date', $showDate)
                ->where('status', 'active')
                ->get();

            foreach ($newSessions as $new) {
                $newStart = $new['start'];
                $newEndClean = $new['end']->copy()->addMinutes($cleaningTime);
                foreach ($existingShowtimes as $exist) {
                    $existStart = $exist->start_date_time;
                    $existEndClean = $exist->end_date_time->addMinutes($cleaningTime);
                    if ($newStart->lt($existEndClean) && $newEndClean->gt($existStart)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Suất chiếu {$new['label']} xung đột với lịch có sẵn."
                        ], 400);
                    }
                }
            }

            return \Illuminate\Support\Facades\DB::transaction(function () use ($movieId, $roomId, $showDate, $ticketPrice, $newSessions) {
                $results = [];
                foreach ($newSessions as $session) {
                    $st = Showtime::create([
                        'movie_id' => $movieId,
                        'room_id' => $roomId,
                        'room_type_id' => $session['room_type_id'],
                        'show_date' => $showDate,
                        'start_time' => $session['start']->format('H:i:s'),
                        'end_time' => $session['end']->format('H:i:s'),
                        'ticket_price' => $ticketPrice,
                        'status' => 'active',
                    ]);
                    $results[] = $st->load(['movie.genres', 'roomType']);
                }
                return response()->json([
                    'status' => 'success',
                    'message' => "Đã tạo thành công " . count($results) . " suất chiếu.",
                    'data' => ManagerShowtimeResource::collection($results)
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
