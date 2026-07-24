<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/affairs/calendar/events', 'POST', [
    'title'      => 'Test Event',
    'location'   => '',
    'event_time' => '',
    'event_date' => '2026-07-25',
]);

// We need to act as an affairs user
$user = App\Models\User::where('role_id', 6)->first();
$request->setUserResolver(function () use ($user) { return $user; });

$controller = new App\Http\Controllers\Api\AffairsController();
$response = $controller->storeCalendarEvent($request);
echo $response->getContent();
