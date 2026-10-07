<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * عميل Google Gemini. المفتاح والموديلات من config/services.php (لا env() هنا
 * حتى يعمل مع config:cache)، والمفتاح يُرسل في الترويسة وليس في الـ URL.
 */
class GeminiClient
{
    public function isConfigured(): bool
    {
        return !empty(config('services.gemini.key')) && !empty(config('services.gemini.models'));
    }

    /**
     * @param  string  $systemPrompt  تعليمات ثابتة (لا تحتوي رسالة المستخدم)
     * @param  array   $history       [['role' => 'user'|'model', 'text' => '...'], ...]
     * @return string|null  نص الرد، أو null عند فشل كل الموديلات
     */
    public function generate(string $systemPrompt, array $history, string $message): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $contents = $this->buildContents($history, $message);
        $key      = (string) config('services.gemini.key');

        foreach ((array) config('services.gemini.models') as $model) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => $key])
                    ->acceptJson()
                    ->timeout((int) config('services.gemini.timeout', 12))
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents'          => $contents,
                        'generationConfig'  => [
                            'temperature'     => 0.3,
                            'maxOutputTokens' => 2000,
                        ],
                    ]);
            } catch (\Throwable $e) {
                Log::warning("Gemini [{$model}] request error: " . $this->scrub($e->getMessage()));
                continue;
            }

            if ($response->successful()) {
                $text = $this->extractText($response->json());
                if ($text !== null) {
                    return $text;
                }
                Log::warning("Gemini [{$model}] returned no usable text (finishReason: "
                    . ($response->json('candidates.0.finishReason') ?? 'none') . ')');
                continue;
            }

            $status = $response->status();
            Log::warning("Gemini [{$model}] HTTP {$status}: " . $this->scrub(mb_substr($response->body(), 0, 300)));

            // مفتاح غير صالح/محظور: لا فائدة من تجربة بقية الموديلات
            if (in_array($status, [401, 403], true)) {
                break;
            }
        }

        return null;
    }

    /** يضمن تناوب user/model ويبدأ بـ user وينتهي بـ model قبل رسالة المستخدم الحالية. */
    protected function buildContents(array $history, string $message): array
    {
        $contents = [];
        $expected = 'user';

        foreach (array_slice($history, -6) as $h) {
            if (!is_array($h)) {
                continue;
            }
            $role = ($h['role'] ?? '') === 'model' ? 'model' : 'user';
            $text = mb_substr(trim((string) ($h['text'] ?? '')), 0, 1500);
            if ($text === '' || $role !== $expected) {
                continue;
            }
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
            $expected   = $role === 'user' ? 'model' : 'user';
        }

        // لا يجوز أن تنتهي السجل برسالة user قبل رسالة user الحالية
        if (!empty($contents) && end($contents)['role'] === 'user') {
            array_pop($contents);
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        return $contents;
    }

    protected function extractText(?array $data): ?string
    {
        $candidate = $data['candidates'][0] ?? null;
        if (!$candidate) {
            return null; // محجوب (promptFeedback) أو لا مرشحين
        }

        $text = '';
        foreach ($candidate['content']['parts'] ?? [] as $part) {
            $text .= $part['text'] ?? '';
        }
        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    /** يمنع تسرب المفتاح إلى السجلات. */
    protected function scrub(string $text): string
    {
        $key = (string) config('services.gemini.key');

        return $key !== '' ? str_replace($key, '***', $text) : $text;
    }
}
