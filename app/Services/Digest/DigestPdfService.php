<?php

namespace App\Services\Digest;

use App\Models\ParentDigest;
use Illuminate\Support\Facades\Log;

/**
 * يحوّل ملخصًا أسبوعيًا محفوظًا إلى ملف PDF عربي (RTL).
 * المحتوى مأخوذ من الحقائق والنص المخزَّنين في parent_digests، فلا يُعاد أي حساب.
 */
class DigestPdfService
{
    private const TONES = [
        'good'      => ['label' => 'أسبوع جيد',     'color' => '#2E7D32', 'bg' => '#E8F5E9'],
        'attention' => ['label' => 'بعض الملاحظات', 'color' => '#B26A00', 'bg' => '#FFF3E0'],
        'concern'   => ['label' => 'يحتاج متابعة',   'color' => '#C62828', 'bg' => '#FFEBEE'],
    ];

    /** @return string محتوى الـ PDF الثنائي، أو '' عند الفشل */
    public function render(ParentDigest $digest): string
    {
        $html = $this->html($digest);

        if (class_exists('\Mpdf\Mpdf')) {
            $prev = error_reporting(0);
            try {
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8', 'format' => 'A4',
                    'margin_left' => 14, 'margin_right' => 14, 'margin_top' => 14, 'margin_bottom' => 14,
                    'autoScriptToLang' => true, 'autoLangToFont' => true, 'useSubsets' => true,
                ]);
                $mpdf->curlTimeout = 3;
                $mpdf->SetDirectionality('rtl');
                $mpdf->SetTitle($digest->title);
                $mpdf->WriteHTML($html);

                return $mpdf->Output('', 'S');
            } catch (\Throwable $e) {
                Log::warning('Digest mPDF error: ' . $e->getMessage());
            } finally {
                error_reporting($prev);
            }
        }

        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            try {
                return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output();
            } catch (\Throwable $e) {
                Log::warning('Digest DomPDF error: ' . $e->getMessage());
            }
        }

        return '';
    }

    public function fileName(ParentDigest $digest): string
    {
        return 'weekly_digest_' . $digest->week_start->format('Y-m-d') . '_' . $digest->student_id . '.pdf';
    }

    /** أرقام/تواريخ تُعزل اتجاهيًا كي لا تنقلب داخل السياق العربي (2 / 5 ، 45% ، 2026-10-04). */
    protected function ltr($v): string
    {
        return '<span dir="ltr">' . e((string) $v) . '</span>';
    }

    /** يحذف الإيموجي (الخط لا يدعمها فتظهر مربعات). */
    protected function stripEmoji(string $t): string
    {
        return trim(preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $t));
    }

    /** صف في جدول الحقائق: عنوان يمينًا وقيمة يسارًا. */
    protected function row(string $label, string $valueHtml, bool $alt): string
    {
        $bg = $alt ? '#FAFAFA' : '#FFFFFF';

        return '<tr><td style="padding:7px 10px;font-size:12px;color:#444;background:' . $bg . ';border-bottom:1px solid #EEE;">'
            . e($label) . '</td><td style="padding:7px 10px;font-size:12px;font-weight:bold;text-align:left;background:'
            . $bg . ';border-bottom:1px solid #EEE;">' . $valueHtml . '</td></tr>';
    }

    protected function section(string $title, array $rows, string $color): string
    {
        if (!$rows) {
            return '';
        }

        $html = '<table style="width:100%;margin-top:14px;border-collapse:collapse;border:1px solid #E3E3E3;"><tr>'
            . '<td colspan="2" style="background:' . $color . ';color:#FFFFFF;padding:7px 10px;font-size:13px;font-weight:bold;">'
            . e($title) . '</td></tr>';

        foreach (array_values($rows) as $i => [$label, $value]) {
            $html .= $this->row($label, $value, $i % 2 === 1);
        }

        return $html . '</table>';
    }

    protected function html(ParentDigest $d): string
    {
        $f       = $d->facts ?? [];
        $a       = $f['attendance'] ?? [];
        $w       = $f['assignments'] ?? [];
        $g       = $f['grades'] ?? [];
        $tone    = self::TONES[$d->tone] ?? self::TONES['good'];
        $c       = $tone['color'];
        $student = $d->student->user->full_name ?? '';

        // --- بطاقات الأرقام الثلاث ---
        $stat = function (string $label, string $value, string $sub) use ($c) {
            return '<td style="width:33%;background:#F6F8F6;border:1px solid #E0E6E0;padding:12px 6px;text-align:center;">'
                . '<div style="font-size:11px;color:#777;">' . e($label) . '</div>'
                . '<div style="font-size:24px;font-weight:bold;color:' . $c . ';padding:4px 0;">' . $value . '</div>'
                . '<div style="font-size:10px;color:#888;">' . $sub . '</div></td>';
        };

        $attended = ($a['present'] ?? 0) + ($a['late'] ?? 0);
        $attValue = ($a['total'] ?? 0) > 0 ? $this->ltr($attended . ' / ' . $a['total']) : '—';
        $attSub   = ($a['total'] ?? 0) > 0 ? 'محاضرة' : 'لا محاضرات';
        $asgValue = ($w['due_this_week'] ?? 0) > 0 ? $this->ltr(($w['submitted'] ?? 0) . ' / ' . $w['due_this_week']) : '—';
        $asgSub   = ($w['due_this_week'] ?? 0) > 0 ? 'واجب' : 'لا واجبات مستحقة';
        $grdValue = isset($g['avg_percent']) ? $this->ltr($g['avg_percent'] . '%') : '—';
        $grdSub   = ($g['count'] ?? 0) > 0 ? 'من العلامات الجديدة' : 'لا علامات جديدة';

        // --- الحضور --- (كل خلية: نص عربي صرف أو رقم صرف، منعًا لانقلاب الاتجاه)
        $att = [];
        if (($a['total'] ?? 0) > 0) {
            $att[] = ['الحضور (من إجمالي المحاضرات)', $this->ltr($attended . ' / ' . $a['total'])];
            if (isset($a['rate'])) {
                $att[] = ['نسبة الحضور', $this->ltr($a['rate'] . '%')];
            }
            if (($a['prev_rate'] ?? null) !== null) {
                $att[] = ['نسبة الحضور في الأسبوع الماضي', $this->ltr($a['prev_rate'] . '%')];
            }
            if (($a['unexcused_absent'] ?? 0) > 0) {
                $att[] = ['غياب بدون عذر', $this->ltr($a['unexcused_absent'])];
            }
            if (($a['excused_absent'] ?? 0) > 0) {
                $att[] = ['غياب بعذر معتمد', $this->ltr($a['excused_absent'])];
            }
            if (($a['late'] ?? 0) > 0) {
                $att[] = ['تأخر', $this->ltr($a['late'])];
            }
            if (($a['absence_days_total'] ?? 0) > 0) {
                $att[] = ['إجمالي أيام الغياب', $this->ltr($a['absence_days_total'])];
            }
        }

        // --- الواجبات ---
        $asg = [];
        if (($w['due_this_week'] ?? 0) > 0) {
            $asg[] = ['مستحقة هذا الأسبوع', $this->ltr($w['due_this_week'])];
            $asg[] = ['تم تسليمها', $this->ltr($w['submitted'] ?? 0)];
            if (($w['late'] ?? 0) > 0) {
                $asg[] = ['سُلّمت متأخرة', $this->ltr($w['late'])];
            }
            if (($w['missing'] ?? 0) > 0) {
                $asg[] = ['فائتة (لم تُسلَّم)', $this->ltr($w['missing'])];
            }
        }
        foreach ($w['upcoming_items'] ?? [] as $item) {
            $asg[] = ['موعد تسليم قادم: ' . $item['course'] . ' - ' . $item['title'], $this->ltr($item['due'])];
        }

        // --- العلامات ---
        $grd = [];
        if (($g['count'] ?? 0) > 0) {
            $grd[] = ['عدد العلامات الجديدة', $this->ltr($g['count'])];
            $grd[] = ['متوسط العلامات', $this->ltr($g['avg_percent'] . '%')];
            if (($g['prev_avg'] ?? null) !== null) {
                $grd[] = ['متوسط الأسبوع الماضي', $this->ltr($g['prev_avg'] . '%')];
            }
            $trend = ['up' => 'ارتفاع', 'down' => 'انخفاض', 'stable' => 'مستقر'][$g['trend'] ?? ''] ?? null;
            if ($trend) {
                $grd[] = ['الاتجاه مقارنة بالأسبوع الماضي', e($trend)];
            }
            foreach ($g['items'] ?? [] as $item) {
                $grd[] = [$item['course'] . ' - ' . $item['title'], $this->ltr($item['score'] . ' / ' . $item['max'])];
            }
        }

        // --- التوصية: آخر سطر في النص بلا إيموجي ---
        $lines = array_values(array_filter(array_map(
            fn ($l) => $this->stripEmoji($l),
            preg_split('/\R/u', (string) $d->body)
        )));
        $recommendation = $lines ? end($lines) : '';

        $narrative = '';
        if ($d->source === 'ai') {
            $narrative = '<div style="margin-top:14px;border:1px solid #E3E3E3;padding:12px;font-size:12px;line-height:1.9;">'
                . nl2br(e($this->stripEmoji((string) $d->body))) . '</div>';
        }

        $recoHtml = ($d->source !== 'ai' && $recommendation !== '')
            ? '<div style="margin-top:14px;background:' . $tone['bg'] . ';border-right:5px solid ' . $c . ';padding:10px 12px;font-size:12px;line-height:1.8;">'
              . '<b style="color:' . $c . ';">التوصية: </b>' . e($recommendation) . '</div>'
            : '';

        return '<html dir="rtl"><body style="font-family:xbriyaz;direction:rtl;text-align:right;color:#222;">'
            // الترويسة
            . '<table style="width:100%;background:' . $c . ';"><tr>'
            . '<td style="padding:12px 14px;font-size:20px;font-weight:bold;color:#FFFFFF;">الملخص الأسبوعي</td>'
            . '<td style="padding:12px 14px;text-align:left;font-size:12px;color:#FFFFFF;">Edu-Bridge</td></tr></table>'
            // الطالب والفترة والحالة
            . '<table style="width:100%;margin-top:12px;"><tr>'
            . '<td style="font-size:17px;font-weight:bold;">' . e($student) . '</td>'
            . '<td style="text-align:left;"><span style="background:' . $tone['bg'] . ';color:' . $c . ';font-size:12px;font-weight:bold;padding:4px 14px;">'
            . e($tone['label']) . '</span></td></tr></table>'
            . '<table style="margin-top:4px;font-size:12px;color:#666;"><tr>'
            . '<td>الفترة: من</td><td style="padding:0 6px;">' . $this->ltr($d->week_start->format('Y-m-d')) . '</td>'
            . '<td>إلى</td><td style="padding:0 6px;">' . $this->ltr($d->week_end->format('Y-m-d')) . '</td></tr></table>'
            // البطاقات
            . '<table style="width:100%;margin-top:12px;border-collapse:separate;border-spacing:6px;"><tr>'
            . $stat('الحضور', $attValue, $attSub)
            . $stat('الواجبات المسلَّمة', $asgValue, $asgSub)
            . $stat('متوسط العلامات', $grdValue, $grdSub)
            . '</tr></table>'
            . $narrative
            . $this->section('الحضور', $att, $c)
            . $this->section('الواجبات', $asg, $c)
            . $this->section('العلامات', $grd, $c)
            . $recoHtml
            . '<div style="margin-top:20px;font-size:10px;color:#999;border-top:1px solid #EEE;padding-top:6px;">'
            . 'أُنشئ هذا الملخص تلقائيًا من بيانات النظام'
            . ($d->source === 'ai' ? ' — صيغ النص بمساعدة الذكاء الاصطناعي والأرقام محسوبة في النظام' : '')
            . '</div><div style="font-size:10px;color:#999;">' . $this->ltr(($d->sent_at ?? $d->created_at)->format('Y-m-d H:i')) . '</div>'
            . '</body></html>';
    }
}
