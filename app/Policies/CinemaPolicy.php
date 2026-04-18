<?php

namespace App\Policies;

use App\Models\Cinema;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Auth\Access\Response;

class CinemaPolicy
{
    /**
     * Determine whether the user can change the cinema status.
     */
    public function updateStatus(User $user, Cinema $cinema): Response
    {
        // Chỉ Admin mới có quyền ẩn rạp
        if (!$user->isAdmin()) {
            return Response::deny('Chỉ Super Admin mới có quyền thay đổi trạng thái rạp.');
        }

        // Nếu muốn chuyển sang inactive, kiểm tra suất chiếu tương lai
        // Giả sử request gửi lên status = inactive
        if (request('status') === 'inactive' || request('status') === 'hidden') {
            $hasFutureTickets = Ticket::whereHas('showtime', function ($query) use ($cinema) {
                $query->whereHas('room', function ($q) use ($cinema) {
                    $q->where('cinema_id', $cinema->cinema_id);
                })
                ->where(function ($q) {
                    $q->where('show_date', '>', now()->toDateString())
                      ->orWhere(function ($q2) {
                          $q2->where('show_date', now()->toDateString())
                            ->where('start_time', '>', now()->toTimeString());
                      });
                });
            })
            ->where('status', 'booked') // Hoặc các status vé hợp lệ
            ->exists();

            if ($hasFutureTickets) {
                return Response::deny('Rạp đang có suất chiếu trong tương lai đã bán vé. Không thể ẩn rạp này!');
            }
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view information.
     */
    public function view(User $user, Cinema $cinema): bool
    {
        return true; // Mọi người có thể xem hoặc tùy chỉnh thêm
    }

    /**
     * Determine whether the user can update the cinema.
     */
    public function update(User $user, Cinema $cinema): bool
    {
        return $user->isAdmin() && $cinema->status === 'active';
    }
}
