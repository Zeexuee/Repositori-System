<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$results = Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM incoming_mails WHERE Field = 'recipient_name'");
echo json_encode($results);
