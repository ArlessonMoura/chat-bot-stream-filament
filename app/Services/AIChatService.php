<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('openai.api_key'),
            'Content-Type' => 'application/json',
        ])
        ->baseUrl(config('openai.base_url'))
        ->timeout(config('openai.timeout'));
    }

    public function streamResponse(string $prompt, callable $onData)
    {
        $fullResponse = '';

        try {
            $response = $this->httpClient
            ->withOptions(['stream' => true])
            ->post('chat/completions?api-version=' . config('openai.api_version'), [
                'model' => config('openai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente útil.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => true,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= $chunk;

                // Processa linhas
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    $line = trim($line);
                    if (empty($line)) continue;

                    if (str_starts_with($line, "data: ")) {
                        $jsonData = substr($line, 6);

                        if ($jsonData === '[DONE]') {
                            break 2;
                        }

                        try {
                            $data = json_decode($jsonData, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $delta = $data['choices'][0]['delta']['content'] ?? '';
                                if ($delta) {
                                    $fullResponse .= $delta;
                                    $onData($delta);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Erro ao processar chunk JSON: ' . $e->getMessage());
                            continue;
                        }
                    }
                }
            }

            return $fullResponse;

        } catch (\Exception $e) {
            Log::error('Erro no streaming: ' . $e->getMessage());
            $errorMessage = 'Erro ao processar resposta da API.';
            $onData($errorMessage);
            return $errorMessage;
        }
    }
}
