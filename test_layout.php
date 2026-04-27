<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rooms = App\Models\Room::all();
foreach ($rooms as $room) {
    echo "Room " . $room->room_id . " uses layout " . $room->seat_layout_id . ". Syncing...\n";
    $success = $room->syncSeatsFromLayout();
    if ($success) {
        echo " - Synced successfully. New valid count: " . $room->valid_seat_count . "\n";
    } else {
        echo " - Sync skipped (no layout or tickets exist).\n";
    }
}
echo "Done.\n";
