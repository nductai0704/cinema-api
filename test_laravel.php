<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$r = App\Models\Room::find(4);
if ($r) {
    print_r(['valid' => $r->valid_seat_count, 'total' => $r->total_seat_count, 'db_cap' => $r->capacity]);
}
