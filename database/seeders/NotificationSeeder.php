<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        // 1. البحث عن ولي الأمر ورئيس القسم بالمسميات الجديدة
        $parent = User::where('role_id', 4)->first();
        $head =  User::where('role_id', 5)->first(); // 👈 تعديل: من head_of_department إلى head

        if ($parent) {
            Notification::create([
                'user_id' => $parent->user_id, // 👈 تعديل: استخدام user_id بدلاً من id
                'title' => 'تنبيه غياب',
                'message' => 'نحيطكم علماً بغياب ابنكم عمر عن محاضرة البرمجة اليوم.',
                'type' => 'attendance',
            ]);
        }

        if ($head) {
            Notification::create([
                'user_id' => $head->user_id, // 👈 تعديل: استخدام user_id بدلاً من id
                'title' => 'طلب إجازة جديد',
                'message' => 'هناك طلب إجازة معلق مقدم من المدرس سامر المحمد بانتظار موافقتك.',
                'type' => 'leave_request',
            ]);
        }
        // إشعارات المدرس
        $teacher = User::where('role_id', 2)->first();
        if ($teacher) {
            $teacherUserId = $teacher->user_id;

            Notification::create([
                'user_id'    => $teacherUserId,
                'title'      => 'تسليم واجب جديد',
                'message'    => 'قام الطالب عمر الخالد بتسليم واجب "تمارين المتغيرات" في مادة أساسيات البرمجة.',
                'type'       => 'academic',
                'is_read'    => false,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ]);

            Notification::create([
                'user_id'    => $teacherUserId,
                'title'      => 'جلسة حضور انتهت',
                'message'    => 'انتهت جلسة تسجيل الحضور لمادة أساسيات البرمجة. حضر 18 من أصل 22 طالباً.',
                'type'       => 'attendance',
                'is_read'    => false,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);

            Notification::create([
                'user_id'    => $teacherUserId,
                'title'      => 'تذكير: موعد تسليم الدرجات',
                'message'    => 'تذكير من الإدارة: آخر موعد لرفع درجات الفصل الأول هو نهاية هذا الأسبوع.',
                'type'       => 'administrative',
                'is_read'    => false,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ]);

            Notification::create([
                'user_id'    => $teacherUserId,
                'title'      => 'تحديث الجدول الدراسي',
                'message'    => 'تم تعديل جدول محاضرات مادة قواعد البيانات ليوم الثلاثاء إلى قاعة 401.',
                'type'       => 'administrative',
                'is_read'    => true,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ]);

            Notification::create([
                'user_id'    => $teacherUserId,
                'title'      => 'طلب تصحيح واجب',
                'message'    => 'هناك 5 واجبات بانتظار التصحيح في مادة أساسيات البرمجة.',
                'type'       => 'academic',
                'is_read'    => true,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]);
        }

        $student = User::where('university_id', '2026100')->first();

        if ($student) {
            $studentId = $student->user_id ?? $student->id;

            // إشعارات أكاديمية
            Notification::create([
                'user_id' => $studentId,
                'title' => 'تم رفع وظيفة جديدة في مادة الرياضيات ',
                'message' => 'قام الأستاذ أحمد برفع وظيفة جديدة في مادة الرياضيات المتقدمة. يرجى التسليم قبل...',
                'type' => 'academic',
                'is_read' => false,
                'created_at' => now()->subHours(2),
            ]);

            Notification::create([
                'user_id' => $studentId,
                'title' => 'تعيين البرنامج الامتحاني',
                'message' => 'تم اعتماد جدول الامتحانات النصفية للفصل الدراسي الحالي، اضغط للتفاصيل.',
                'type' => 'academic',
                'is_read' => true,
                'created_at' => now()->subHours(5),
            ]);

            // إشعارات إدارية
            Notification::create([
                'user_id' => $studentId,
                'title' => 'تحديد عطلة رسمية',
                'message' => 'بمناسبة عيد المعلم، تعلن إدارة المعهد عن تعطيل الدوام الرسمي يوم الخميس القادم.',
                'type' => 'administrative',
                'is_read' => false,
                'created_at' => now(),
            ]);

            Notification::create([
                'user_id' => $studentId,
                'title' => 'تنبيه اشتراك',
                'message' => 'يرجى المبادرة بتسديد القسط الجامعي الثاني قبل نهاية الشهر الحالي لتجنب الغرامات...',
                'type' => 'administrative',
                'is_read' => true,
                'created_at' => now()->subDays(4),
            ]);
        }
    }
    }

