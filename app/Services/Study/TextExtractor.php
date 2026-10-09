<?php

namespace App\Services\Study;

use Smalot\PdfParser\Parser;
use ZipArchive;

/**
 * يستخرج نص محاضرة من PDF أو DOCX أو PPTX. لا OCR: الملف الممسوح صورًا يُرفض بصراحة بدل توليد محتوى من لا شيء.
 */
class TextExtractor
{
    public const EXTENSIONS = ['pdf', 'docx', 'pptx'];

    /** @throws StudyException */
    public function extract(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            throw new StudyException('ملف المحاضرة غير موجود على الخادم.');
        }
        if (filesize($absolutePath) > config('study.max_file_kb') * 1024) {
            throw new StudyException('حجم الملف أكبر من المسموح لتوليد مواد المذاكرة.');
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        try {
            $text = match ($ext) {
                'pdf' => $this->pdf($absolutePath),
                'docx' => $this->docx($absolutePath),
                'pptx' => $this->pptx($absolutePath),
                default => throw new StudyException('نوع الملف غير مدعوم. المدعوم: PDF وWord (docx) وPowerPoint (pptx).'),
            };
        } catch (StudyException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new StudyException('تعذّرت قراءة الملف (قد يكون تالفًا أو محميًا بكلمة سر).');
        }

        $text = $this->clean($text);
        if (mb_strlen($text) < config('study.min_chars')) {
            throw new StudyException('لم يُستخرج نص كافٍ من الملف. إن كان ممسوحًا ضوئيًا (صورًا) فلا يمكن قراءته؛ ارفع نسخة نصية.');
        }

        return $text;
    }

    private function pdf(string $path): string
    {
        return (new Parser())->parseFile($path)->getText();
    }

    private function docx(string $path): string
    {
        $xml = $this->zipEntry($path, 'word/document.xml');
        // نهاية كل فقرة سطر جديد قبل نزع الوسوم
        $xml = preg_replace('#</w:p>#', "\n", $xml);

        return $this->stripXml($xml);
    }

    private function pptx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new StudyException('تعذّر فتح ملف العرض التقديمي.');
        }
        $slides = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
                $slides[(int) $m[1]] = $zip->getFromIndex($i);
            }
        }
        $zip->close();
        ksort($slides);

        return implode("\n\n", array_map(fn ($x) => $this->stripXml(preg_replace('#</a:p>#', "\n", $x)), $slides));
    }

    private function zipEntry(string $path, string $entry): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new StudyException('تعذّر فتح الملف.');
        }
        $data = $zip->getFromName($entry);
        $zip->close();
        if ($data === false) {
            throw new StudyException('بنية الملف غير مألوفة.');
        }

        return $data;
    }

    private function stripXml(string $xml): string
    {
        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** يوحّد الفراغات ويقصّ إلى الحد الأقصى المرسل للنموذج. */
    public function clean(string $text): string
    {
        $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text) ?? $text;   // محارف التحكم
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return mb_substr(trim($text), 0, (int) config('study.max_chars'));
    }
}
