<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AIChatService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://plataforma-f2ai-azure-openai.openai.azure.com/openai/deployments/gpt-4o-mini/',
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'stream' => true,
        ]);
    }   
    

    public function streamResponse(string $prompt, callable $onData)
    {
        $response = $this->client->post('chat/completions?api-version=2025-01-01-preview', [
            'json' => [
                'model' => env('OPENAI_MODEL'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente útil.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => true,
            ],
        ]);



        // $response = $this->client->withOptions([
        //             'stream' => true,
        //             'read_timeout' => 300,
        //             'timeout' => 300,
        //         ])->post('chat/completions?api-version=2025-01-01-preview', [
        //     'json' => [
        //         'model' => env('OPENAI_MODEL'),
        //         'messages' => [
        //             ['role' => 'system', 'content' => 'Você é um assistente útil.'],
        //             ['role' => 'user', 'content' => $prompt],
        //         ],
        //         'stream' => true,
        //     ],
        // ]);

        $body = $response->getBody();

        return $body;   
        
        // while (!$body->eof()) {
        //     $line = trim($body->read(1024));
        //     if (!empty($line) && str_starts_with($line, "data: ")) {
        //         $json = substr($line, 6);
        //         if ($json !== '[DONE]') {
        //             L
        //             $data = json_decode($json, true);
        //             $delta = $data['choices'][0]['delta']['content'] ?? '';
        //             if ($delta) {
        //                 $onData($delta);
        //             }
        //         }
        //     }
        // }

    }
}
