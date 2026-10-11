<?php

use App\Support\NameTransliterator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users' => 'user_id', 'university_ids' => null] as $table => $pk) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'full_name_ar')) {
                    $t->string('full_name_ar')->nullable()->after('full_name');
                }
                if (!Schema::hasColumn($table, 'full_name_en')) {
                    $t->string('full_name_en')->nullable()->after('full_name_ar');
                }
            });

            DB::table($table)->whereNotNull('full_name')->get()->each(function ($row) use ($table) {
                $name = trim((string) $row->full_name);
                if ($name === '') {
                    return;
                }
                if (NameTransliterator::hasArabic($name)) {
                    $ar = $name;
                    $en = NameTransliterator::toEnglish($name);
                } else {
                    $en = $name;
                    $ar = NameTransliterator::toArabic($name);
                }
                $key = isset($row->user_id) ? ['user_id' => $row->user_id] : ['id' => $row->id];
                DB::table($table)->where($key)->update(['full_name_ar' => $ar, 'full_name_en' => $en]);
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'university_ids'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    foreach (['full_name_ar', 'full_name_en'] as $c) {
                        if (Schema::hasColumn($table, $c)) {
                            $t->dropColumn($c);
                        }
                    }
                });
            }
        }
    }
};
