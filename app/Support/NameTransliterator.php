<?php

namespace App\Support;

/**
 * Approximate, offline transliteration of personal names between Arabic and English.
 * Results are a best-effort starting point and should be reviewed manually.
 */
class NameTransliterator
{
    private const COMMON_AR_EN = [
        'محمد' => 'Mohammad', 'أحمد' => 'Ahmad', 'احمد' => 'Ahmad', 'عبد' => 'Abd', 'عبدالله' => 'Abdullah',
        'عبدالرحمن' => 'Abdulrahman', 'علي' => 'Ali', 'عمر' => 'Omar', 'خالد' => 'Khaled', 'يوسف' => 'Yousef',
        'ابراهيم' => 'Ibrahim', 'إبراهيم' => 'Ibrahim', 'حسن' => 'Hassan', 'حسين' => 'Hussein', 'مصطفى' => 'Mustafa',
        'فاطمة' => 'Fatima', 'مريم' => 'Maryam', 'هدى' => 'Huda', 'شبلي' => 'Shibli', 'سارة' => 'Sara',
        'نور' => 'Nour', 'ليلى' => 'Layla', 'زينب' => 'Zainab', 'عائشة' => 'Aisha', 'سمير' => 'Samir',
        'ياسر' => 'Yasser', 'طارق' => 'Tareq', 'ماهر' => 'Maher', 'رنا' => 'Rana', 'دعاء' => 'Doaa',
    ];

    private const AR_EN = [
        'ا' => 'a', 'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ء' => '', 'ؤ' => 'o', 'ئ' => 'e', 'ب' => 'b', 'ت' => 't',
        'ث' => 'th', 'ج' => 'j', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'th', 'ر' => 'r', 'ز' => 'z',
        'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh',
        'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'ه' => 'h', 'و' => 'w',
        'ي' => 'y', 'ى' => 'a', 'ة' => 'a', 'ـ' => '',
    ];

    private const EN_AR_DIGRAPHS = [
        'sh' => 'ش', 'kh' => 'خ', 'th' => 'ث', 'gh' => 'غ', 'ch' => 'تش', 'ph' => 'ف', 'ou' => 'و', 'oo' => 'و',
        'ee' => 'ي', 'aa' => 'ا',
    ];

    private const EN_AR = [
        'a' => 'ا', 'b' => 'ب', 'c' => 'ك', 'd' => 'د', 'e' => 'ي', 'f' => 'ف', 'g' => 'غ', 'h' => 'ه', 'i' => 'ي',
        'j' => 'ج', 'k' => 'ك', 'l' => 'ل', 'm' => 'م', 'n' => 'ن', 'o' => 'و', 'p' => 'ب', 'q' => 'ق', 'r' => 'ر',
        's' => 'س', 't' => 'ت', 'u' => 'و', 'v' => 'ف', 'w' => 'و', 'x' => 'كس', 'y' => 'ي', 'z' => 'ز',
    ];

    public static function hasArabic(?string $s): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', (string) $s);
    }

    public static function hasLatin(?string $s): bool
    {
        return (bool) preg_match('/[A-Za-z]/', (string) $s);
    }

    private const EXTRA_AR_EN = [
        'وسيم' => 'Wasim', 'وليد' => 'Waleed', 'عيسى' => 'Issa', 'جودي' => 'Judy', 'كردي' => 'Kurdi',
        'ابو' => 'Abu', 'أبو' => 'Abu', 'ام' => 'Umm', 'أم' => 'Umm', 'خليل' => 'Khalil', 'محمود' => 'Mahmoud',
        'سعيد' => 'Saeed', 'كريم' => 'Karim', 'جميل' => 'Jamil', 'سامر' => 'Samer', 'بسام' => 'Bassam',
    ];

    private static function arabicWordToEnglish(string $w): string
    {
        $tokens = [];
        $chars = preg_split('//u', $w, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $idx => $ch) {
            $isFirst = $idx === 0;
            $prevIsConsonant = !empty($tokens) && !in_array(end($tokens), ['a', 'i', 'u', 'o', 'e'], true);
            $isLast = $idx === count($chars) - 1;
            if ($ch === 'و') {
                $tokens[] = ($prevIsConsonant && !$isLast) ? 'u' : ($prevIsConsonant ? 'u' : 'w');
            } elseif ($ch === 'ي') {
                $tokens[] = ($prevIsConsonant) ? ($isLast ? 'i' : 'i') : 'y';
            } elseif ($ch === 'ع') {
                $tokens[] = $isFirst ? 'a' : "'";
            } elseif ($ch === 'ة') {
                $tokens[] = 'a';
            } else {
                $tokens[] = self::AR_EN[$ch] ?? $ch;
            }
        }
        $vowels = ['a', 'i', 'u', 'o', 'e', ''];
        $res = '';
        $n = count($tokens);
        for ($i = 0; $i < $n; $i++) {
            $res .= $tokens[$i];
            $next = $tokens[$i + 1] ?? null;
            if ($next !== null && !in_array($tokens[$i], $vowels, true) && !in_array($next, $vowels, true)) {
                $res .= 'a';
            }
        }
        return str_replace("'", '', $res);
    }

    public static function toEnglish(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name));
        $out = [];
        foreach ($words as $w) {
            $w = preg_replace('/[\x{064B}-\x{065F}]/u', '', $w); // strip diacritics
            if (isset(self::COMMON_AR_EN[$w])) {
                $out[] = self::COMMON_AR_EN[$w];
                continue;
            }
            if (isset(self::EXTRA_AR_EN[$w])) {
                $out[] = self::EXTRA_AR_EN[$w];
                continue;
            }
            $prefix = '';
            if (mb_strlen($w) > 3 && mb_substr($w, 0, 2) === 'ال') {
                $prefix = 'Al-';
                $w = mb_substr($w, 2);
                if (isset(self::COMMON_AR_EN[$w]) || isset(self::EXTRA_AR_EN[$w])) {
                    $out[] = $prefix . (self::COMMON_AR_EN[$w] ?? self::EXTRA_AR_EN[$w]);
                    continue;
                }
            }
            $out[] = $prefix . ucfirst(self::arabicWordToEnglish($w));
        }
        return implode(' ', $out);
    }

    public static function toArabic(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name));
        $out = [];
        foreach ($words as $w) {
            $w = strtolower($w);
            $t = '';
            $i = 0;
            $len = strlen($w);
            while ($i < $len) {
                $two = substr($w, $i, 2);
                if (isset(self::EN_AR_DIGRAPHS[$two])) {
                    $t .= self::EN_AR_DIGRAPHS[$two];
                    $i += 2;
                    continue;
                }
                $c = $w[$i];
                // Vowels inside a word (not first letter) are mostly dropped, except long ones handled above.
                if ($i > 0 && $i < $len - 1 && in_array($c, ['a', 'e', 'o', 'u', 'i'], true)) {
                    $i++;
                    continue;
                }
                $t .= self::EN_AR[$c] ?? $c;
                $i++;
            }
            $out[] = $t;
        }
        return implode(' ', $out);
    }
}
