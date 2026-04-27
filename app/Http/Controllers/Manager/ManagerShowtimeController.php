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
        // Nhờ BelongsToCinema Trait, tự động lọc showtimes của Manager's Cinema
        $showtimes = Showtime::with(['movie', 'room.roomType'])->orderBy('show_date')->orderBy('start_time')->paginate(20);
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

            // 1. Kiểm tra phim (Bao gồm cả việc phim có bị ẩn bởi Global Scope không)
            $movie = Movie::find($data['movie_id']);
            if (!$movie) {
                return response()->json(['message' => 'Phim không tồn tại hoặc đã bị ẩn (Inactive)!'], 404);
            }

            // 2. Kiểm tra phòng
            $room = Room::find($data['room_id']);
            if (!$room) {
                return response()->json(['message' => 'Phòng chiếu không tồn tại trong hệ thống của bạn!'], 404);
            }

            // 3. Tính toán end_time tự động
            $duration = (int) ($movie->duration ?? 0);
            $startTime = Carbon::parse($data['show_date'] . ' ' . $data['start_time']);
            $endTime = $startTime->copy()->addMinutes($duration);
            
            $data['start_time'] = $startTime->format('H:i:s');
            $data['end_time'] = $endTime->format('H:i:s');

            // 4. Conflict Checker (kiểm tra đụng giờ chiếu trong cùng 1 phòng)
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
                    'message' => 'Lịch chiếu bị trùng hoặc quá sát giờ nghỉ (Cần ' . $cleaningTime . ' phút dọn phòng).',
                    'conflicts' => $conflicts
                ], 409);
            }

            $showtime = Showtime::create($data);

            return response()->json([
                'message' => 'Lên lịch suất chiếu thành công.',
                'data' => $showtime->load(['movie', 'room'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Dữ liệu không hợp lệ', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $showtime = Showtime::with(['movie', 'room.roomType'])->findOrFail($id);
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
                'showtimes.*.format' => 'nullable|string', // VD: "2D", "IMAX"
            ]);

            $movieId = $request->movie_id;
            $roomId = $request->room_id;
            $showDate = $request->show_date;
            $ticketPrice = $request->ticket_price;
            $bulkShowtimes = $request->showtimes;

            // 1. Kiểm tra phim và phòng
            $movie = Movie::find($movieId);
            $room = Room::find($roomId);
            if (!$movie || !$room) {
                return response()->json(['message' => 'Phim hoặc Phòng chiếu không tồn tại.'], 404);
            }

            // Lấy danh sách RoomType của Cinema hiện tại để map tên -> ID
            $roomTypes = \App\Models\RoomType::all()->pluck('room_type_id', 'name');

            $duration = (int) ($movie->duration ?? 0);
            $cleaningTime = (int) config('cinema.cleaning_time_minutes', 15);

            // Chuyển mảng input sang định dạng Carbon để dễ so sánh
            $newSessions = [];
            foreach ($bulkShowtimes as $idx => $st) {
                $start = Carbon::parse("$showDate {$st['start_time']}");
                
                // Nếu FE không gửi end_time, tự tính dựa trên thời lượng phim
                if (empty($st['end_time'])) {
                    $end = $start->copy()->addMinutes($duration);
                } else {
                    $end = Carbon::parse("$showDate {$st['end_time']}");
                }

                // Tìm room_type_id từ tên format gửi lên
                $roomTypeId = isset($st['format']) ? ($roomTypes[strtoupper($st['format'])] ?? null) : null;

                $newSessions[] = [
                    'start' => $start,
                    'end' => $end,
                    'label' => "{$st['start_time']} - " . $end->format('H:i'),
                    'original_idx' => $idx,
                    'room_type_id' => $roomTypeId
                ];
            }

            // 2. Kiểm tra xung đột NỘI BỘ (trong chính danh sách gửi lên)
            for ($i = 0; $i < count($newSessions); $i++) {
                for ($j = $i + 1; $j < count($newSessions); $j++) {
                    $a = $newSessions[$i];
                    $b = $newSessions[$j];
                    
                    // Thêm thời gian dọn khi so sánh
                    $aEndWithClean = $a['end']->copy()->addMinutes($cleaningTime);
                    $bEndWithClean = $b['end']->copy()->addMinutes($cleaningTime);

                    if ($a['start']->lt($bEndWithClean) && $aEndWithClean->gt($b['start'])) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Xung đột nội bộ: Suất {$a['label']} và {$b['label']} bị chồng lấn (Cần {$cleaningTime}p dọn phòng)."
                        ], 400);
                    }
                }
            }

            // 3. Kiểm tra xung đột với DATABASE
            $existingShowtimes = Showtime::where('room_id', $roomId)
                ->where('show_date', $showDate)
                ->where('status', 'active')
                ->get();

            foreach ($newSessions as $new) {
                $newStart = $new['start'];
                $newEndWithClean = $new['end']->copy()->addMinutes($cleaningTime);

                foreach ($existingShowtimes as $exist) {
                    $existStart = $exist->start_date_time;
                    $existEndWithClean = $exist->end_date_time->addMinutes($cleaningTime);

                    if ($newStart->lt($existEndWithClean) && $newEndWithClean->gt($existStart)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Suất chiếu {$new['label']} bị xung đột với lịch đã có trong hệ thống."
                        ], 400);
                    }
                }
            }

            // 4. Lưu dữ liệu (Database Transaction)
            return \Illuminate\Support\Facades\DB::transaction(function () use ($movieId, $roomId, $showDate, $ticketPrice, $newSessions) {
                $createdCount = 0;
                $results = [];

                foreach ($newSessions as $session) {
                    $st = Showtime::create([
                        'movie_id' => $movieId,
                        'room_id' => $roomId,
                        'room_type_id' => $session['room_type_id'], // Đã map từ format string
                        'show_date' => $showDate,
                        'start_time' => $session['start']->format('H:i:s'),
                        'end_time' => $session['end']->format('H:i:s'),
                        'ticket_price' => $ticketPrice,
                        'status' => 'active',
                    ]);
                    $results[] = $st->load('roomType');
                    $createdCount++;
                }

                return response()->json([
                    'status' => 'success',
                    'message' => "Đã tạo thành công {$createdCount} suất chiếu.",
                    'data' => ManagerShowtimeResource::collection($results)
                ], 201);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
