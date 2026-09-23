<?php

namespace App\Traits;

trait FaceRecognitionTrait
{
    /**
     * استخراج متجهات ميزات الصورة بحجم موحد 48x48 رمادي وتطبيع التباين
     */
    public function extractImageVector($imageBinary): array
    {
        try {
            $src = @imagecreatefromstring($imageBinary);
            if (!$src) return [];

            $w = imagesx($src);
            $h = imagesy($src);
            if ($w <= 0 || $h <= 0) return [];

            $resized = imagecreatetruecolor(48, 48);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, 48, 48, $w, $h);
            imagedestroy($src);

            $pixels = [];
            $sum = 0;
            for ($y = 0; $y < 48; $y++) {
                for ($x = 0; $x < 48; $x++) {
                    $rgb = imagecolorat($resized, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $val = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                    $pixels[] = $val;
                    $sum += $val;
                }
            }
            imagedestroy($resized);

            $count = count($pixels);
            if ($count === 0) return [];

            $mean = $sum / $count;
            $variance = 0;
            foreach ($pixels as $v) {
                $variance += ($v - $mean) * ($v - $mean);
            }
            $std = sqrt($variance / $count);
            if ($std < 0.001) $std = 1.0;

            $normalized = [];
            foreach ($pixels as $v) {
                $normalized[] = ($v - $mean) / $std;
            }
            return $normalized;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * حساب تشابه جيب التمام (Cosine Similarity) بين متجهين
     * ينتج قيمة مئوية بين 0% و 100%
     */
    public function calculateFaceSimilarity(array $stored, array $current): float
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

        $similarity = $dot / $denom; // Cosine similarity in range [-1.0, 1.0]
        $score = (($similarity + 1.0) / 2.0) * 100.0;
        return round(max(0.0, min(100.0, $score)), 1);
    }
}
