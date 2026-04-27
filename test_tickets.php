<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rooms = App\Models\Room::all();
foreach ($rooms as $room) {
    $hasTickets = $room->showtimes()->whereHas('tickets')->exists();
    echo "Room " . $room->room_id . " has tickets: " . ($hasTickets ? 'YES' : 'NO') . "\n";
}
