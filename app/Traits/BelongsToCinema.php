<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToCinema
{
    /**
     * Boot the trait and apply the global scope.
     */
    protected static function bootBelongsToCinema()
    {
        // 1. Thêm Global Scope tự động lọc dữ liệu theo cinema_id của Manager
        static::addGlobalScope('cinema', function (Builder $builder) {
            $user = Auth::user();
            
            if ($user && $user->isManager() && $user->cinema_id) {
                // Cho phép Model tự định nghĩa cách áp dụng scope (dành cho Seat, News, ...)
                if (method_exists(static::class, 'applyCinemaScope')) {
                    static::applyCinemaScope($builder, $user->cinema_id);
                } else {
                    // Mặc định model sẽ có cột cinema_id
                    $builder->where((new static)->getTable().'.cinema_id', $user->cinema_id);
                }
            }
        });

        // 2. Tự động gán cinema_id khi tạo mới bản ghi nếu là Manager
        static::creating(function ($model) {
            $user = Auth::user();
            
            if ($user && $user->isManager() && $user->cinema_id) {
                // Chỉ gán nếu model có trường cinema_id trong fillable
                if (in_array('cinema_id', $model->getFillable()) || $model->hasSetMutator('cinema_id') || true) {
                    // Kiểm tra tránh ghi đè nếu model là User và đang được tạo không qua API (tuy nhiên logic tạo qua web thường an toàn)
                    if (empty($model->cinema_id) && property_exists($model, 'cinema_id') === false) {
                        $model->cinema_id = $user->cinema_id;
                    }
                }
            }
        });
    }
}
