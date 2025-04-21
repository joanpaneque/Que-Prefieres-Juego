<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    /**
     * Envía un prompt a la API de OpenAI y devuelve la respuesta.
     *
     * @param string $prompt El texto del prompt a enviar.
     * @param string $model El modelo de OpenAI a utilizar (por defecto: gpt-4o).
     * @return string|null La respuesta de la API o null si hay un error.
     */
    public static function prompt(string $prompt, string $model = 'gpt-4.1'): ?string
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            Log::error('OpenAI API key not configured in config/services.php.');
            return null;
        }

        $apiUrl = 'https://api.openai.com/v1/chat/completions';

        $response = Http::withToken($apiKey)
            ->timeout(60) // Aumentar el timeout si es necesario
            ->post($apiUrl, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 150, // Puedes ajustar esto
                'temperature' => 0.7, // Puedes ajustar esto
            ]);


        if ($response->successful()) {
            // Acceder al contenido de la respuesta
            $choices = $response->json('choices');
            if (isset($choices[0]['message']['content'])) {
                return trim($choices[0]['message']['content']);
            }
            Log::error('Unexpected OpenAI API response structure: ' . $response->body());
            return null;
        } else {
            Log::error('OpenAI API request failed: ' . $response->status() . ' - ' . $response->body());
            return null;
        }
    }
} 