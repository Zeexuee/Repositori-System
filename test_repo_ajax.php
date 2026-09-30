<?php
$request = Illuminate\Http\Request::create('/repository?month=2026-09&search=ssdad', 'GET');
$request->headers->set('X-Requested-With', 'XMLHttpRequest');
$request->headers->set('Accept', 'application/json');

$controller = app()->make(App\Http\Controllers\RepositoryController::class);
try {
    $response = $controller->index($request);
    echo "SUCCESS\n";
    echo substr($response->getContent(), 0, 500);
} catch (\Exception $e) {
    echo "ERROR:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
