<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$users = DB::table('users')
    ->join('teachers','users.user_id','=','teachers.user_id')
    ->select('users.user_id','users.full_name','users.first_name','users.last_name','users.username','users.email','teachers.teacher_id')
    ->get();
foreach ($users as $u) {
    echo "tid:{$u->teacher_id} | full:{$u->full_name} | first:{$u->first_name} last:{$u->last_name} | user:{$u->username} | email:{$u->email}\n";
}
