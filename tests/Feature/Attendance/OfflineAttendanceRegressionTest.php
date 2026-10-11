<?php

namespace Tests\Feature\Attendance;

use App\Support\QrTokens;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * اختبار توثيقي لسلوك الحضور الأوفلاين الحالي (المرحلة 0 - البند 5 في الخطة).
 *
 * يوثق المشكلة P1: إذا مسح الطالب الرمز بدون إنترنت، واستمر المعلم بتدوير الرموز
 * لأكثر من 90 ثانية، ثم زامن الطالب لاحقاً، فإن السيرفر الحالي يرفضه بسبب expired_qr.
 */
class OfflineAttendanceRegressionTest extends TestCase
{
    use MakesAcademicData;

    private array $student;
    private array $teacher;
    private int $courseId;
    private int $lessonId;
    private int $sessionId;
    private string $initialToken = 'INITIALTOKEN01234567890123456789';

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('local');
        config(['attendance.require_face' => false]); // للتركيز على فحص وقت الـ QR

        $dept    = $this->makeDepartment();
        $program = $this->makeProgram($dept);

        $this->teacher = $this->makeTeacher();
        $this->student = $this->makeStudent(['academic_year' => 'السنة الأولى'], $program);
        DB::table('students')->where('student_id', $this->student['student_id'])->update(['level' => 'السنة الأولى']);

        $this->courseId = $this->makeCourse($program, ['year' => 1]);
        $this->assignTeacher($this->courseId, $this->teacher['teacher_id']);
        $this->enroll($this->student['student_id'], $this->courseId);

        $this->lessonId = DB::table('lessons')->insertGetId([
            'course_id'  => $this->courseId,
            'teacher_id' => $this->teacher['teacher_id'],
            'title'      => 'Offline Session Test',
            'type'       => 'session',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id'          => $this->lessonId,
            'qr_token'           => $this->initialToken,
            'expires_at'         => now()->addSeconds(30),
            'session_expires_at' => now()->addMinutes(60),
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    /**
     * التحقق من حل المشكلة P1: المسح الأوفلاين يُقبل بنجاح عند المزامنة
     * حتى بعد تدوير الرموز لعدة دقائق، طالما كان وقت المسح ضمن نافذة صلاحية الرمز.
     */
    public function test_offline_scan_is_accepted_after_rotations_via_timeline(): void
    {
        $scanTime = now()->toDateTimeString();

        // محاكاة مرور 5 دقائق وتدوير الرمز 8 مرات (كل 30 ثانية رمز جديد)
        for ($i = 1; $i <= 8; $i++) {
            $newToken = 'NEWTOKEN' . str_pad((string) $i, 24, 'X', STR_PAD_RIGHT);
            Carbon::setTestNow(now()->addSeconds(30));
            QrTokens::rotate($this->sessionId, $newToken);
        }

        // الآن الطالب يعود للإنترنت ويزامن الرمز الذي مسحه قبل 4 دقائق
        $response = $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', [
                'qr_token'        => $this->initialToken,
                'scanned_at'      => $scanTime,
                'client_scan_id'  => 'SCAN-UUID-TEST-001',
                'time_confidence' => 'high',
            ]);

        // تم القبول بنجاح بفضل جدول attendance_session_tokens
        $response->assertOk()
            ->assertJson(['success' => true]);

        // التحقق من تسجيل الحضور كوسم أوفلاين ومعرف فريد
        $this->assertDatabaseHas('attendance', [
            'student_id'     => $this->student['student_id'],
            'lesson_id'      => $this->lessonId,
            'status'         => 'present',
            'source'         => 'offline',
            'client_scan_id' => 'SCAN-UUID-TEST-001',
        ]);

        Carbon::setTestNow(); // إعادة ضبط الوقت
    }

    /**
     * التحقق من منع التكرار (Idempotency): إرسال نفس المسح مرتين
     * يعيد نفس النتيجة بنجاح دون إنشاء سجل مكرر أو خطأ 409 مضلل.
     */
    public function test_duplicate_offline_scan_is_idempotent(): void
    {
        $scanTime = now()->toDateTimeString();

        $payload = [
            'qr_token'       => $this->initialToken,
            'scanned_at'     => $scanTime,
            'client_scan_id' => 'SCAN-IDEMPOTENT-002',
        ];

        // المحاولة الأولى
        $first = $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', $payload);
        $first->assertOk()->assertJson(['success' => true]);

        // المحاولة الثانية بنفس client_scan_id
        $second = $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', $payload);
        $second->assertOk()->assertJson([
            'success'   => true,
            'is_replay' => true,
        ]);

        // التأكد من وجود سجل حضور واحد فقط في قاعدة البيانات
        $this->assertSame(
            1,
            DB::table('attendance')
                ->where('student_id', $this->student['student_id'])
                ->where('lesson_id', $this->lessonId)
                ->count()
        );
    }

    /**
     * التحقق من نقطة مزامنة الدفعة POST /api/student/attendance/sync
     */
    public function test_batch_sync_endpoint_processes_multiple_scans(): void
    {
        $scanTime = now()->toDateTimeString();

        $response = $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/sync', [
                'scans' => [
                    [
                        'qr_token'        => $this->initialToken,
                        'scanned_at'      => $scanTime,
                        'client_scan_id'  => 'BATCH-SCAN-001',
                        'time_confidence' => 'high',
                    ],
                    [
                        'qr_token'        => 'INVALID_FAKE_TOKEN',
                        'scanned_at'      => $scanTime,
                        'client_scan_id'  => 'BATCH-SCAN-002',
                        'time_confidence' => 'high',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.success', true)
            ->assertJsonPath('results.0.client_scan_id', 'BATCH-SCAN-001')
            ->assertJsonPath('results.1.success', false)
            ->assertJsonPath('results.1.client_scan_id', 'BATCH-SCAN-002')
            ->assertJsonPath('results.1.reject_reason', 'expired_qr');

        // السجل الأول تم تسجيله بنجاح
        $this->assertDatabaseHas('attendance', [
            'student_id'     => $this->student['student_id'],
            'client_scan_id' => 'BATCH-SCAN-001',
            'status'         => 'present',
        ]);
    }

    /**
     * التحقق من المرحلة 3 (P3): الجلسة الأوفلاين لا تسجل غياباً فورياً عند الإنهاء
     * بل تؤجل الغياب حتى انقضاء مهلة المزامنة.
     */
    public function test_offline_session_defers_absence_on_end_session(): void
    {
        // وسم الجلسة بأنها تدعم الأوفلاين
        DB::table('attendance_sessions')
            ->where('id', $this->sessionId)
            ->update(['offline_mode' => 'standard']);

        // طالب آخر مسجل لم يزامن بعد
        $otherStudent = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->makeProgram($this->makeDepartment()));
        $this->enroll($otherStudent['student_id'], $this->courseId);

        // المعلم ينهي الجلسة
        $response = $this->actAs($this->teacher['user'])
            ->postJson("/api/teacher/attendance/session/{$this->sessionId}/end");

        $response->assertOk()
            ->assertJsonPath('status', 'pending_sync');

        // لم يُسجل غياب بعد لطالب الأوفلاين! (حماية من الإنذارات الخاطئة)
        $this->assertSame(
            0,
            DB::table('attendance')
                ->where('student_id', $otherStudent['student_id'])
                ->where('lesson_id', $this->lessonId)
                ->count()
        );

        // التحقق من تعيين مهلة المزامنة
        $this->assertNotNull(
            DB::table('attendance_sessions')->where('id', $this->sessionId)->value('sync_deadline_at')
        );
    }

    /**
     * التحقق من أن الأمر المجدول attendance:finalize-sessions
     * يسجل الغياب فقط بعد انقضاء مهلة المزامنة sync_deadline_at.
     */
    public function test_finalize_sessions_command_records_absence_after_deadline(): void
    {
        DB::table('attendance_sessions')
            ->where('id', $this->sessionId)
            ->update([
                'is_active'        => false,
                'offline_mode'     => 'standard',
                'sync_deadline_at' => now()->addHours(48),
            ]);

        // طالب غائب
        $absentStudent = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->makeProgram($this->makeDepartment()));
        $this->enroll($absentStudent['student_id'], $this->courseId);

        // تشغيل الأمر قبل انتهاء المهلة: لا يحدث شيء
        \Illuminate\Support\Facades\Artisan::call('attendance:finalize-sessions');
        $this->assertSame(0, DB::table('attendance')->where('student_id', $absentStudent['student_id'])->count());

        // تقديم الوقت لما بعد انتهاء المهلة (49 ساعة)
        Carbon::setTestNow(now()->addHours(49));

        // تشغيل الأمر المجدول
        \Illuminate\Support\Facades\Artisan::call('attendance:finalize-sessions');

        // الآن فقط تم تسجيل الغياب رسمياً
        $this->assertDatabaseHas('attendance', [
            'student_id' => $absentStudent['student_id'],
            'lesson_id'  => $this->lessonId,
            'status'     => 'absent',
        ]);

        // تم وسم الجلسة بأنها finalized
        $this->assertNotNull(
            DB::table('attendance_sessions')->where('id', $this->sessionId)->value('finalized_at')
        );

        Carbon::setTestNow();
    }

    /**
     * المرحلة 2 (P2-TOTP): التحقق من صحة توليد وتدقيق رموز TOTP أوفلاين.
     */
    public function test_totp_token_generation_and_verification(): void
    {
        $secret = 'secret-key-32-chars-0123456789';
        $uuid   = '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d';
        $now    = 1700000000;

        $payload = \App\Support\TotpQrService::generatePayload($secret, $uuid, $now);
        $this->assertStringStartsWith('edu-bridge://attendance?v=2&sid=' . $uuid, $payload);

        // تدقيق الحمولة بنفس اللحظة
        $this->assertTrue(\App\Support\TotpQrService::verify($payload, $secret, $uuid, $now));

        // تدقيق الحمولة بعد 20 ثانية (ضمن نفس الدورة أو تسامح ±1)
        $this->assertTrue(\App\Support\TotpQrService::verify($payload, $secret, $uuid, $now + 20));

        // تدقيق الحمولة بعد 120 ثانية (أكثر من دورتين: خارج التسامح)
        $this->assertFalse(\App\Support\TotpQrService::verify($payload, $secret, $uuid, $now + 120));

        // تدقيق حمولة بمفتاح سري خاطئ
        $this->assertFalse(\App\Support\TotpQrService::verify($payload, 'wrong-secret-key-01234567890123', $uuid, $now));
    }

    /**
     * المرحلة 2: مسح رمز TOTP أوفلاين يُقبل بنجاح عند المزامنة اللاحقة.
     */
    public function test_totp_qr_payload_is_accepted_in_offline_scan(): void
    {
        $secret = bin2hex(random_bytes(16));
        $uuid   = (string) \Illuminate\Support\Str::uuid();

        // تحديث الجلسة ببيانات TOTP
        DB::table('attendance_sessions')->where('id', $this->sessionId)->update([
            'session_uuid'   => $uuid,
            'session_secret' => $secret,
            'offline_mode'   => 'standard',
        ]);

        $scanUnix = now()->timestamp;
        $scanTimeString = now()->toDateTimeString();

        // المعلم في وضع الطيران يولد الرمز محلياً
        $totpPayload = \App\Support\TotpQrService::generatePayload($secret, $uuid, $scanUnix);

        // محاكاة مرور 10 دقائق وعودة الطالب للإنترنت
        Carbon::setTestNow(now()->addMinutes(10));

        // الطالب يزامن المسح
        $response = $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', [
                'qr_token'        => $totpPayload,
                'scanned_at'      => $scanTimeString,
                'client_scan_id'  => 'TOTP-SCAN-TEST-001',
                'time_confidence' => 'high',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendance', [
            'student_id'     => $this->student['student_id'],
            'lesson_id'      => $this->lessonId,
            'status'         => 'present',
            'client_scan_id' => 'TOTP-SCAN-TEST-001',
        ]);

        Carbon::setTestNow();
    }

    /**
     * المرحلة 2: رفض رمز TOTP إذا كان خارج نافذة التسامح الزمنية (تزوير أو تأخير مفرط).
     */
    public function test_totp_qr_payload_outside_tolerance_window_is_rejected(): void
    {
        $secret = bin2hex(random_bytes(16));
        $uuid   = (string) \Illuminate\Support\Str::uuid();

        DB::table('attendance_sessions')->where('id', $this->sessionId)->update([
            'session_uuid'   => $uuid,
            'session_secret' => $secret,
            'offline_mode'   => 'standard',
        ]);

        // رمز قديم جداً (قبل 10 دقائق من وقت المسح)
        $oldUnix = now()->subMinutes(10)->timestamp;
        $stalePayload = \App\Support\TotpQrService::generatePayload($secret, $uuid, $oldUnix);

        $response = $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', [
                'qr_token'        => $stalePayload,
                'scanned_at'      => now()->toDateTimeString(),
                'client_scan_id'  => 'TOTP-EXPIRED-TEST-002',
                'time_confidence' => 'high',
            ]);

        $response->assertStatus(400)
            ->assertJson(['reject_reason' => 'expired_qr']);
    }

    /**
     * المرحلة 2: التحقق من نقطة جلب وتجهيز حصص اليوم للمعلم مع مفاتيح TOTP.
     */
    public function test_teacher_today_sessions_provisions_scheduled_classes_with_secrets(): void
    {
        $todayEnglish = now()->englishDayOfWeek;

        // إضافة حصة في جدول اليوم
        DB::table('schedules')->insert([
            'course_id'   => $this->courseId,
            'teacher_id'  => $this->teacher['user']->user_id,
            'day'         => $todayEnglish,
            'start_time'  => '09:00:00',
            'end_time'    => '11:00:00',
            'room'        => 'القاعة 101',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->actAs($this->teacher['user'])
            ->getJson('/api/teacher/attendance/today-sessions');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'sessions')
            ->assertJsonPath('sessions.0.course_id', $this->courseId)
            ->assertJsonPath('sessions.0.room', 'القاعة 101');

        $sessionData = $response->json('sessions.0');
        $this->assertNotEmpty($sessionData['session_uuid']);
        $this->assertNotEmpty($sessionData['session_secret']);
        $this->assertTrue($sessionData['is_active']);
    }

    /**
     * المرحلة 4 (P4-الوضع الصارم): تسجيل وإلغاء تفعيل المفتاح العام لجهاز المعلم Ed25519.
     */
    public function test_teacher_can_register_and_revoke_device_key(): void
    {
        $keypair   = sodium_crypto_sign_keypair();
        $publicKey = sodium_bin2hex(sodium_crypto_sign_publickey($keypair));

        // تسجيل مفتاح الجهاز
        $response = $this->actAs($this->teacher['user'])
            ->postJson('/api/teacher/device-key', [
                'device_id'  => 'DEVICE-PHONE-101',
                'public_key' => $publicKey,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('teacher_device_keys', [
            'teacher_user_id' => $this->teacher['user']->user_id,
            'device_id'       => 'DEVICE-PHONE-101',
            'revoked_at'      => null,
        ]);

        // إلغاء تفعيل المفتاح فورياً
        $revokeRes = $this->actAs($this->teacher['user'])
            ->postJson('/api/teacher/device-key/revoke', [
                'device_id' => 'DEVICE-PHONE-101',
            ]);

        $revokeRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull(
            DB::table('teacher_device_keys')->where('device_id', 'DEVICE-PHONE-101')->value('revoked_at')
        );
    }

    /**
     * المرحلة 4: إيصال المعلم الموقع بـ Ed25519 يؤكد حضور الطالب ويرفع مستوى الثقة (Countersigning).
     */
    public function test_teacher_receipt_countersigns_attendance_and_boosts_confidence(): void
    {
        $keypair    = sodium_crypto_sign_keypair();
        $publicKey  = sodium_bin2hex(sodium_crypto_sign_publickey($keypair));
        $secretKey  = sodium_crypto_sign_secretkey($keypair);
        $deviceId   = 'TEACHER-DEVICE-ED25519';
        $clientScan = 'STRICT-SCAN-UUID-888';
        $teacherTime = now()->toIso8601String();

        // 1. تسجيل مفتاح جهاز المعلم
        DB::table('teacher_device_keys')->insert([
            'teacher_user_id' => $this->teacher['user']->user_id,
            'device_id'       => $deviceId,
            'public_key'      => $publicKey,
            'registered_at'   => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // 2. تحديث الجلسة بـ session_uuid
        $sessionUuid = (string) \Illuminate\Support\Str::uuid();
        DB::table('attendance_sessions')->where('id', $this->sessionId)->update([
            'session_uuid' => $sessionUuid,
            'offline_mode' => 'strict',
        ]);

        // 3. وجود سجل حضور أولي بحالة ثقة منخفضة (مثلاً بسبب اختلاف ساعة أو وضع صارم)
        DB::table('attendance')->insert([
            'student_id'      => $this->student['student_id'],
            'lesson_id'       => $this->lessonId,
            'session_id'      => $this->sessionId,
            'attendance_date' => now()->toDateString(),
            'status'          => 'present',
            'source'          => 'offline',
            'client_scan_id'  => $clientScan,
            'time_confidence' => 'low',
            'countersigned'   => false,
            'review_status'   => 'pending',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // 4. المعلم يوقّع الإيصال بمفتاح جهازه Ed25519
        $message   = "edu-bridge-receipt|{$sessionUuid}|{$this->student['student_id']}|{$clientScan}|{$teacherTime}";
        $signature = sodium_bin2hex(sodium_crypto_sign_detached($message, $secretKey));

        // 5. المعلم يرفع الإيصال عبر POST /api/teacher/attendance/sync
        $response = $this->actAs($this->teacher['user'])
            ->postJson('/api/teacher/attendance/sync', [
                'receipts' => [
                    [
                        'session_uuid'   => $sessionUuid,
                        'student_id'     => $this->student['student_id'],
                        'client_scan_id' => $clientScan,
                        'device_id'      => $deviceId,
                        'teacher_time'   => $teacherTime,
                        'signature'      => $signature,
                    ]
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('results.0.success', true)
            ->assertJsonPath('results.0.countersigned', true);

        // 6. التحقق من ترقية سجل الحضور إلى high و countersigned = true
        $this->assertDatabaseHas('attendance', [
            'student_id'      => $this->student['student_id'],
            'client_scan_id'  => $clientScan,
            'countersigned'   => 1,
            'time_confidence' => 'high',
            'review_status'   => 'confirmed',
        ]);

        // التحقق من حفظ الإيصال في attendance_teacher_receipts
        $this->assertDatabaseHas('attendance_teacher_receipts', [
            'session_id'     => $this->sessionId,
            'client_scan_id' => $clientScan,
            'verified'       => 1,
        ]);
    }

    /**
     * المرحلة 4: رفض الإيصالات الموقعة بمفتاح جهاز مسحوب (Revoked Key).
     */
    public function test_revoked_teacher_key_rejects_receipts(): void
    {
        $keypair   = sodium_crypto_sign_keypair();
        $publicKey = sodium_bin2hex(sodium_crypto_sign_publickey($keypair));
        $secretKey = sodium_crypto_sign_secretkey($keypair);
        $deviceId  = 'REVOKED-DEVICE-001';

        DB::table('teacher_device_keys')->insert([
            'teacher_user_id' => $this->teacher['user']->user_id,
            'device_id'       => $deviceId,
            'public_key'      => $publicKey,
            'registered_at'   => now()->subDays(10),
            'revoked_at'      => now()->subHour(), // مسحوب قبل ساعة
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $sessionUuid = (string) \Illuminate\Support\Str::uuid();
        DB::table('attendance_sessions')->where('id', $this->sessionId)->update(['session_uuid' => $sessionUuid]);

        $message   = "edu-bridge-receipt|{$sessionUuid}|{$this->student['student_id']}|SCAN-001|" . now()->toIso8601String();
        $signature = sodium_bin2hex(sodium_crypto_sign_detached($message, $secretKey));

        $response = $this->actAs($this->teacher['user'])
            ->postJson('/api/teacher/attendance/sync', [
                'receipts' => [
                    [
                        'session_uuid'   => $sessionUuid,
                        'student_id'     => $this->student['student_id'],
                        'client_scan_id' => 'SCAN-001',
                        'device_id'      => $deviceId,
                        'teacher_time'   => now()->toIso8601String(),
                        'signature'      => $signature,
                    ]
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('results.0.success', false)
            ->assertJsonPath('results.0.error', 'revoked_key');
    }

    /**
     * المرحلة 4: رفض الإيصالات ذات التوقيع المزور أو المعدل (Bad Receipt Signature).
     */
    public function test_tampered_receipt_signature_is_rejected(): void
    {
        $keypair   = sodium_crypto_sign_keypair();
        $publicKey = sodium_bin2hex(sodium_crypto_sign_publickey($keypair));
        $deviceId  = 'LEGIT-DEVICE-002';

        DB::table('teacher_device_keys')->insert([
            'teacher_user_id' => $this->teacher['user']->user_id,
            'device_id'       => $deviceId,
            'public_key'      => $publicKey,
            'registered_at'   => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $sessionUuid = (string) \Illuminate\Support\Str::uuid();
        DB::table('attendance_sessions')->where('id', $this->sessionId)->update(['session_uuid' => $sessionUuid]);

        // توقيع خاطئ أو مزور
        $fakeSignature = str_repeat('aa', 64);

        $response = $this->actAs($this->teacher['user'])
            ->postJson('/api/teacher/attendance/sync', [
                'receipts' => [
                    [
                        'session_uuid'   => $sessionUuid,
                        'student_id'     => $this->student['student_id'],
                        'client_scan_id' => 'SCAN-TAMPERED-002',
                        'device_id'      => $deviceId,
                        'teacher_time'   => now()->toIso8601String(),
                        'signature'      => $fakeSignature,
                    ]
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('results.0.success', false)
            ->assertJsonPath('results.0.error', 'bad_receipt');
    }
}


