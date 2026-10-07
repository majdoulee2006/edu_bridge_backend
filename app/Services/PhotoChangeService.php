<?php

namespace AppServices;

use IlluminateSupportFacadesDB;
use IlluminateSupportFacadesStorage;
use IlluminateHttpUploadedFile;
use AppModelsNotification;
use AppServicesFcmService;

class PhotoChangeService
{
    /**
     * تقديم طلب تغيير صورة من قبل الطالب
     */
    public static function submitRequest(int $userId, UploadedFile $photoFile): array
    {
        $user = DB::table('users')->where('user_id', $userId)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }

        // حذف أي طلب معلق سابق
        $old = DB::table('photo_change_requests')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($old) {
            if ($old->new_photo && Storage::disk('public')->exists($old->new_photo)) {
                Storage::disk('public')->delete($old->new_photo);
            }
            DB::table('photo_change_requests')->where('id', $old->id)->delete();
        }

        // حفظ الصورة الجديدة في مجلد photo_requests
        $newPath = $photoFile->store('photo_requests', 'public');

        $id = DB::table('photo_change_requests')->insertGetId([
            'user_id'    => $userId,
            'old_photo'  => $user->avatar,
            'new_photo'  => $newPath,
            'status'     => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'تم إرسال طلب تغيير صورة الوجه بنجاح، وهو قيد مراجعة الشؤون',
            'request_id' => $id,
            'path' => $newPath
        ];
    }

    /**
     * موافقة موظف الشؤون على الطلب
     */
    public static function approveRequest(int $requestId, ?int $approverId = null): array
    {
        $req = DB::table('photo_change_requests')->where('id', $requestId)->where('status', 'pending')->first();
        if (!$req) {
            return ['success' => false, 'message' => 'الطلب غير موجود أو تمت معالجته مسبقاً'];
        }

        // حذف الصورة القديمة إذا كانت موجودة
        if ($req->old_photo && Storage::disk('public')->exists($req->old_photo)) {
            Storage::disk('public')->delete($req->old_photo);
        }

        // تحديث صورة المستخدم في جدول users
        DB::table('users')->where('user_id', $req->user_id)->update(['avatar' => $req->new_photo]);

        // تحديث الصورة المرجعية في جدول students وتصفير البصمة لتوليدها فوراً
        DB::table('students')->where('user_id', $req->user_id)->update([
            'reference_photo' => $req->new_photo,
            'face_embedding'  => null,
        ]);

        // تغيير حالة الطلب إلى approved
        DB::table('photo_change_requests')->where('id', $requestId)->update([
            'status'     => 'approved',
            'updated_at' => now(),
        ]);

        // إرسال إشعار داخلي في النظام
        DB::table('notifications')->insert([
            'user_id'    => $req->user_id,
            'sender_id'  => $approverId,
            'title'      => 'تمت الموافقة على تغيير صورة الوجه',
            'message'    => 'تمت الموافقة من قبل شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك بنجاح.',
            'type'       => 'academic',
            'category'   => 'academic',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // إرسال إشعار FCM للموبايل
        try {
            FcmService::sendToUser(
                $req->user_id,
                'تمت الموافقة على تغيير صورة الوجه',
                'تمت الموافقة من قبل شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك.',
                ['type' => 'academic']
            );
        } catch (\Throwable $e) {}

        return ['success' => true, 'message' => 'تمت الموافقة على تغيير الصورة واعتمادها بنجاح'];
    }

    /**
     * رفض الطلب من قبل موظف الشؤون
     */
    public static function rejectRequest(int $requestId, ?int $approverId = null, ?string $reason = null): array
    {
        $req = DB::table('photo_change_requests')->where('id', $requestId)->where('status', 'pending')->first();
        if (!$req) {
            return ['success' => false, 'message' => 'الطلب غير موجود أو تمت معالجته مسبقاً'];
        }

        // حذف الصورة المرفوعة للطلب
        if ($req->new_photo && Storage::disk('public')->exists($req->new_photo)) {
            Storage::disk('public')->delete($req->new_photo);
        }

        DB::table('photo_change_requests')->where('id', $requestId)->update([
            'status'     => 'rejected',
            'updated_at' => now(),
        ]);

        $rejectMsg = $reason 
            ? "تم رفض طلب تحديث صورة بصمة الوجه. السبب: $reason" 
            : 'تم رفض طلب تحديث صورة بصمة الوجه من قبل شؤون الطلاب، يرجى مراجعة إدارة الشؤون.';

        DB::table('notifications')->insert([
            'user_id'    => $req->user_id,
            'sender_id'  => $approverId,
            'title'      => 'رفض طلب تغيير صورة الوجه',
            'message'    => $rejectMsg,
            'type'       => 'academic',
            'category'   => 'academic',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            FcmService::sendToUser(
                $req->user_id,
                'رفض طلب تغيير صورة الوجه',
                $rejectMsg,
                ['type' => 'academic']
            );
        } catch (\Throwable $e) {}

        return ['success' => true, 'message' => 'تم رفض الطلب بنجاح'];
    }
}
