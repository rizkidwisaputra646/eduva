<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey;
    protected string $primaryModel;
    protected array $fallbackModels;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key') 
            ?: env('GEMINI_API_KEY', env('GEMINI_API'));

        $this->primaryModel = config('services.gemini.model') 
            ?: env('GEMINI_MODEL', 'gemini-flash-lite-latest');

        $this->fallbackModels = [
            'gemini-flash-lite-latest',
            'gemini-3.1-flash-lite-preview',
            'gemini-flash-latest',
            'gemini-3-flash-preview',
        ];
    }

    /**
     * Check if Gemini API key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Generate response from Gemini.
     *
     * @param string $systemInstruction
     * @param array $messages [{role: 'user'|'assistant', content: string}]
     * @param float $temperature
     * @return array ['success' => bool, 'content' => string, 'error' => string|null]
     */
    public function chat(string $systemInstruction, array $messages, float $temperature = 0.7): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'API Key Gemini belum dikonfigurasi di file .env (GEMINI_API_KEY).',
            ];
        }

        // Prepare contents payload for Gemini API with strict role alternation
        $normalized = [];
        $lastRole = null;

        foreach ($messages as $msg) {
            $rawContent = trim($msg['content'] ?? '');
            if ($rawContent === '') {
                continue;
            }

            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';

            // Gemini requires the very first turn to be 'user'
            if (empty($normalized) && $role === 'model') {
                continue;
            }

            // Merge consecutive turns with identical role to comply with Gemini API
            if ($role === $lastRole) {
                $lastIndex = count($normalized) - 1;
                $normalized[$lastIndex]['parts'][0]['text'] .= "\n\n" . $rawContent;
            } else {
                $normalized[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $rawContent]
                    ]
                ];
                $lastRole = $role;
            }
        }

        // Gemini requires the final turn in the prompt to be 'user'
        while (!empty($normalized) && end($normalized)['role'] !== 'user') {
            array_pop($normalized);
        }

        // If no user message was passed
        if (empty($normalized)) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'Pesan percakapan tidak boleh kosong.',
            ];
        }

        $generationConfig = [
            'temperature' => $temperature,
            'maxOutputTokens' => 2048,
            'topP' => 0.95,
        ];

        return $this->executeRequest($systemInstruction, $normalized, $generationConfig, 30);
    }

    /**
     * Generate a structured JSON response from Gemini (used by AI Question Generator).
     *
     * @return array ['success' => bool, 'content' => string, 'model' => ?string, 'error' => ?string]
     */
    public function generateJson(
        string $systemInstruction,
        string $userPrompt,
        float $temperature = 0.4,
        int $maxOutputTokens = 8192
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'API Key Gemini belum dikonfigurasi di file .env (GEMINI_API_KEY).',
            ];
        }

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $userPrompt],
                ],
            ],
        ];

        $generationConfig = [
            'temperature' => $temperature,
            'maxOutputTokens' => $maxOutputTokens,
            'topP' => 0.95,
            'responseMimeType' => 'application/json',
        ];

        return $this->executeRequest($systemInstruction, $contents, $generationConfig, 60);
    }

    /**
     * Execute Gemini API call across primary and fallback models.
     */
    protected function executeRequest(
        string $systemInstruction,
        array $contents,
        array $generationConfig,
        int $timeoutSeconds = 30
    ): array {
        $modelsToTry = array_unique(array_merge([$this->primaryModel], $this->fallbackModels));
        $lastError = 'Gagal menghubungi server AI.';

        foreach ($modelsToTry as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($this->apiKey);

            $payload = [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'contents' => $contents,
                'generationConfig' => $generationConfig,
            ];

            try {
                $response = Http::timeout($timeoutSeconds)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if ($response->successful()) {
                    $candidate = $response->json('candidates.0');
                    $parts = $candidate['content']['parts'] ?? [];
                    
                    // Concatenate all text parts (accounting for thinking/reasoning parts)
                    $text = '';
                    foreach ($parts as $part) {
                        if (!empty($part['text'])) {
                            $text .= $part['text'];
                        }
                    }
                    $text = trim($text);

                    if ($text !== '') {
                        return [
                            'success' => true,
                            'content' => $text,
                            'model' => $model,
                            'error' => null,
                        ];
                    }

                    // Check if stopped due to safety or other finish reasons
                    $finishReason = $candidate['finishReason'] ?? null;
                    if ($finishReason === 'SAFETY') {
                        return [
                            'success' => false,
                            'content' => '',
                            'error' => 'Pesan tidak dapat diproses karena terdeteksi melanggar kebijakan keamanan konten Google AI.',
                        ];
                    }
                }

                $status = $response->status();
                $errBody = $response->json();
                $message = $errBody['error']['message'] ?? $response->body();

                Log::warning("Gemini API call failed with model {$model}: HTTP {$status} - {$message}");

                if ($status === 404 && str_contains($message, 'is no longer available')) {
                    $lastError = "Model {$model} tidak tersedia, mencoba model alternatif...";
                    continue;
                }

                if ($status === 429) {
                    return [
                        'success' => false,
                        'content' => '',
                        'error' => 'Batas kuota / rate-limit API Gemini tercapai saat ini. Silakan coba kembali dalam beberapa saat.',
                    ];
                }

                $lastError = "Google AI API ({$status}): " . ($errBody['error']['message'] ?? 'Terjadi kesalahan pada layanan AI.');
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error("Gemini API Connection Timeout with model {$model}: " . $e->getMessage());
                $lastError = 'Koneksi ke server AI timeout / gangguan jaringan. Silakan periksa koneksi internet Anda dan coba lagi.';
            } catch (\Throwable $e) {
                Log::error("Gemini API Exception with model {$model}: " . $e->getMessage());
                $lastError = 'Terjadi kesalahan sistem saat memproses pesan: ' . $e->getMessage();
            }
        }

        return [
            'success' => false,
            'content' => '',
            'error' => $lastError,
        ];
    }
}
