# خطة ربط واجهة الواجبات مع الداتا بيز [جاري العمل]

## الخطوات
- [ ] 1. تعديل Migration - إضافة teacher_id لجدول assignments
- [ ] 2. تحديث AssignmentController - إضافة destroy, edit, update
- [ ] 3. إعادة كتابة assignments.blade.php - ربط كامل مع البيانات الديناميكية
- [ ] 4. إضافة مسارات routes للـ destroy/edit/update
- [ ] 5. إضافة seed data للواجبات في DatabaseSeeder
- [ ] 6. اختبار التطبيق وتحديث TODO

## الحالة الحالية
- [x] AssignmentController - Assignments مع Courses ✅ **جاري**
- [ ] DashboardController - Announcements من DB
- [ ] ScheduleController - Schedules مع relations  
- [ ] NotificationController - Notifications مع read status
- [ ] MessageController - Messages + contacts
- [x] LectureController - Lessons مع Courses/Departments ✅
- [ ] AttendanceController - Courses/Departments dropdowns

