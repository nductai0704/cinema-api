<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::enableQueryLog();
$r = App\Models\Room::find(4);
if ($r) {
    echo "Syncing room 4...\n";
    $r->syncSeatsFromLayout();
    print_r(DB::getQueryLog());
}
