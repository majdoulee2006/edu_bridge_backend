<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentDigest;
use App\Models\Parents;
use App\Services\Digest\DigestPdfService;
use Illuminate\Http\Request;

/**
 * الملخصات الأسبوعية من وجهة نظر ولي الأمر.
 * كل استعلام مقيّد بـ parent_user_id = المستخدم الحالي، فلا يرى أحد ملخص غيره.
 */
class ParentDigestController extends Controller
{
    public function index(Request $request)
    {
        $query = ParentDigest::with('student.user:user_id,full_name')
            ->where('parent_user_id', $request->user()->user_id)
            ->whereNotNull('sent_at')
            ->orderByDesc('week_start');

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->query('student_id'));
        }

        $page = $query->paginate(min((int) $request->query('per_page', 10), 30));

        return response()->json([
            'success'      => true,
            'unread_count' => ParentDigest::where('parent_user_id', $request->user()->user_id)
                ->whereNotNull('sent_at')->whereNull('read_at')->count(),
            'data'         => collect($page->items())->map(fn ($d) => $this->present($d, false))->values(),
            'meta'         => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $digest = $this->findOwned($request, $id);
        if (!$digest) {
            return response()->json(['success' => false, 'message' => 'الملخص غير موجود'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->present($digest, true)]);
    }

    public function markRead(Request $request, $id)
    {
        $digest = $this->findOwned($request, $id);
        if (!$digest) {
            return response()->json(['success' => false, 'message' => 'الملخص غير موجود'], 404);
        }

        if (!$digest->read_at) {
            $digest->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function pdf(Request $request, $id, DigestPdfService $pdf)
    {
        $digest = $this->findOwned($request, $id);
        if (!$digest) {
            return response()->json(['success' => false, 'message' => 'الملخص غير موجود'], 404);
        }

        $content = $pdf->render($digest);
        if ($content === '') {
            return response()->json(['success' => false, 'message' => 'تعذّر إنشاء ملف PDF'], 500);
        }

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf->fileName($digest) . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    public function settings(Request $request)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        return response()->json([
            'success' => true,
            'data'    => ['digest_enabled' => (bool) ($parent->digest_enabled ?? true)],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate(['digest_enabled' => 'required|boolean']);

        $parent = Parents::where('user_id', $request->user()->user_id)->first();
        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $parent->update(['digest_enabled' => $data['digest_enabled']]);

        return response()->json([
            'success' => true,
            'message' => $parent->digest_enabled ? 'تم تفعيل الملخص الأسبوعي' : 'تم إيقاف الملخص الأسبوعي',
            'data'    => ['digest_enabled' => $parent->digest_enabled],
        ]);
    }

    protected function findOwned(Request $request, $id): ?ParentDigest
    {
        return ParentDigest::with('student.user:user_id,full_name')
            ->where('parent_user_id', $request->user()->user_id)
            ->whereNotNull('sent_at')
            ->find($id);
    }

    protected function present(ParentDigest $d, bool $withFacts): array
    {
        $row = [
            'id'         => $d->id,
            'student_id' => $d->student_id,
            'student'    => $d->student->user->full_name ?? '',
            'week_start' => $d->week_start->toDateString(),
            'week_end'   => $d->week_end->toDateString(),
            'tone'       => $d->tone,
            'title'      => $d->title,
            'body'       => $d->body,
            'source'     => $d->source,
            'is_read'    => $d->read_at !== null,
            'sent_at'    => optional($d->sent_at)->toIso8601String(),
        ];

        if ($withFacts) {
            $row['facts'] = $d->facts;
        }

        return $row;
    }
}
