<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Models\Movie;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ManagerShowtimeController extends Controller
{
    public function index()
    {
        // Nhờ BelongsToCinema Trait, tự động lọc showtimes của Manager's Cinema
        $showtimes = Showtime::with(['movie', 'room'])->orderBy('show_date')->orderBy('start_time')->paginate(20);
        return response()->json($showtimes);
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
        $showtime = Showtime::with(['movie', 'room'])->findOrFail($id);
        return response()->json($showtime);
    }
}
