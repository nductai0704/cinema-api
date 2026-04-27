<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $c = app(\App\Http\Controllers\Manager\ManagerShowtimeController::class);
    
    $req1 = Request::create('/api', 'POST', [
        'movie_id' => 3, 'room_id' => 2, 'show_date' => '2026-05-15', 'price_standard' => 75000, 'price_vip' => 90000, 'price_double' => 150000,
        'showtimes' => [['start_time' => '10:00', 'end_time' => '12:00']]
    ]);
    // Mock validation
    $req1->merge(['movie_id' => 3, 'room_id' => 2, 'show_date' => '2026-05-15', 'price_standard' => 75000, 'price_vip' => 90000, 'price_double' => 150000, 'showtimes' => [['start_time' => '10:00', 'end_time' => '12:00']]]);
    $r1 = $c->bulkStore($req1);
    
    $req2 = Request::create('/api', 'POST', [
        'movie_id' => 3, 'room_id' => 3, 'show_date' => '2026-05-15', 'price_standard' => 75000, 'price_vip' => 90000, 'price_double' => 150000,
        'showtimes' => [['start_time' => '10:00', 'end_time' => '12:00']]
    ]);
    $r2 = $c->bulkStore($req2);
    
    echo 'r1 status: ' . $r1->status() . "\n";
    echo 'r2 status: ' . $r2->status() . "\n";
    echo $r2->content();
} catch (\Exception $e) {
    echo $e->getMessage();
}
