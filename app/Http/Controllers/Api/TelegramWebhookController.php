<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TelegramBotHandler;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected $telegramHandler;

    public function __construct(TelegramBotHandler $telegramHandler)
    {
        $this->telegramHandler = $telegramHandler;
    }

    public function handle(Request $request)
    {
        try {
            $update = $request->all();
            
            // For debugging purposes, you can log the incoming update
            // Log::info('Telegram Update: ', $update);

            if (isset($update['message']) || isset($update['callback_query'])) {
                dispatch(function () use ($update) {
                    $this->telegramHandler->handleUpdate($update);
                })->afterResponse();
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Telegram Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * عرض صفحة الماسح الذكي (QR & Face) لمستخدمي التيليغرام
     */
    public function showScanner(Request $request)
    {
        $chatId = $request->query('chat_id');
        return view('telegram.scanner', compact('chatId'));
    }

    /**
     * استقبال تسجيل الحضور من صفحة التيليغرام
     */
    public function recordAttendanceFromScanner(Request $request)
    {
        $request->validate([
            'chat_id'        => 'required',
            'qr_token'       => 'required|string',
            'face_image'     => 'nullable|string',
            'face_embedding' => 'nullable|array',
        ]);

        $user = \App\Models\User::where('telegram_chat_id', (string)$request->chat_id)->first();
        if (!$user || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على حساب طالب مقترن بهذا التيليغرام. يرجى تسجيل الدخول أولاً.',
            ], 404);
        }

        $student = $user->student;
        $token = trim($request->qr_token);

        // استخراج التوكن إذا كان مدمجاً في رابط
        if (str_starts_with($token, 'edu-bridge://attendance?token=')) {
            $token = str_replace('edu-bridge://attendance?token=', '', $token);
        }

        // فحص جلسة الحضور
        $session = \App\Models\AttendanceSession::where('qr_token', $token)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'رمز QR غير صالح أو انتهت صلاحية جلسة التفقد.',
            ], 400);
        }

        // التحقق من بصمة الوجه ومطابقتها مع بصمة الطالب المرجعية
        $faceEmbedding = $request->face_embedding;
        $faceStatus = 'verified';
        $faceScore = null;

        if ($faceEmbedding && is_array($faceEmbedding) && count($faceEmbedding) > 0) {
            $storedEmbedding = $student->face_embedding;
            if (!empty($storedEmbedding) && is_array($storedEmbedding)) {
                $faceScore = $this->calculateFaceSimilarity($storedEmbedding, $faceEmbedding);

                // التحقق من نسبة التطابق (الحد الأدنى 35% كما في تطبيق الفلاتر والويب)
                if ($faceScore < 35.0) {
                    return response()->json([
                        'success' => false,
                        'message' => "❌ تعذر التحقق من بصمة الوجه! (نسبة التطابق: {$faceScore}% وهي غير كافية لمطابقة حساب الطالب). تأكد أنك صاحب الحساب وأنك تواجه الكاميرا بإضاءة جيدة.",
                        'face_score' => $faceScore,
                    ], 403);
                }

                // تحديث تدريجي للمرجع
                $updated = [];
                foreach ($storedEmbedding as $i => $v) {
                    $updated[$i] = $v * 0.85 + ($faceEmbedding[$i] ?? $v) * 0.15;
                }
                $student->update(['face_embedding' => $updated]);
            } else {
                // حفظ البصمة للمرة الأولى
                $student->update(['face_embedding' => $faceEmbedding]);
                $faceScore = 100.0;
            }
        }

        // حفظ صورة الوجه
        $savedFaceImagePath = null;
        if ($request->face_image) {
            try {
                $imgData = $request->face_image;
                if (str_contains($imgData, ',')) {
                    $imgData = explode(',', $imgData)[1];
                }
                $decoded = base64_decode($imgData);
                $filename = 'face_tg_' . $student->student_id . '_' . time() . '.jpg';
                $destDir = public_path('uploads/faces');
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                file_put_contents($destDir . '/' . $filename, $decoded);
                $savedFaceImagePath = 'uploads/faces/' . $filename;
            } catch (\Exception $e) {
                Log::error("Failed saving face image: " . $e->getMessage());
            }
        }

        // تسجيل أو تحديث الحضور
        $existing = \App\Models\Attendance::where('student_id', $student->student_id)
            ->where('lesson_id', $session->lesson_id)
            ->whereDate('attendance_date', today())
            ->first();

        if ($existing && $existing->status === 'present') {
            return response()->json([
                'success' => true,
                'message' => 'لقد قمت بتسجيل حضورك في هذه الجلسة مسبقاً!',
            ]);
        }

        if ($existing) {
            $existing->update([
                'status'          => 'present',
                'excuse_status'   => 'none',
                'face_image'      => $savedFaceImagePath ?? $existing->face_image,
                'face_score'      => $faceScore,
                'face_status'     => 'verified',
            ]);
        } else {
            \App\Models\Attendance::create([
                'student_id'      => $student->student_id,
                'lesson_id'       => $session->lesson_id,
                'status'          => 'present',
                'attendance_date' => today(),
                'excuse_status'   => 'none',
                'face_image'      => $savedFaceImagePath,
                'face_score'      => $faceScore,
                'face_status'     => 'verified',
            ]);
        }

        // إرسال إشعار تأكيد في محادثة التيليغرام
        $session->loadMissing('lesson.course');
        $courseTitle = $session->lesson->course->title ?? $session->lesson->title ?? 'المحاضرة';
        $timeStr = now()->format('h:i A');

        $this->telegramHandler->sendMessage(
            $request->chat_id,
            "🎉 **تم تسجيل حضورك بنجاح!** 🎓\n\n📘 **المادة:** {$courseTitle}\n⏰ **الوقت:** {$timeStr}\n✅ **الحالة:** حاضر (تم التحقق من الوجه ورمز QR)"
        );

        return response()->json([
            'success' => true,
            'message' => "تم تسجيل حضورك بنجاح في مادة ({$courseTitle})!",
        ]);
    }

    private function calculateFaceSimilarity(array $stored, array $current): float
    {
        $len = min(count($stored), count($current));
        if ($len === 0) return 0.0;

        $dot = 0.0; $normA = 0.0; $normB = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot   += $stored[$i]  * $current[$i];
            $normA += $stored[$i]  * $stored[$i];
            $normB += $current[$i] * $current[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);
        if ($denom == 0) return 0.0;

        $similarity = $dot / $denom;
        return max(0.0, min(100.0, round($similarity * 100, 2)));
    }
}
