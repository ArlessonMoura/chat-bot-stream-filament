<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para comunicação com API da OpenAI/Azure OpenAI
 *
 * Este serviço gerencia:
 * - Conexão HTTP com a API de IA
 * - Processamento de streaming de respostas em tempo real
 * - Tratamento de erros e logging
 * - Configuração centralizada via config/openai.php
 */
class AIChatService
{
    /** @var \Illuminate\Http\Client\PendingRequest Cliente HTTP configurado */
    private $httpClient;

    /**
     * Construtor - Configura o cliente HTTP uma única vez
     *
     * Benefícios de fazer no construtor:
     * - Performance: não recria a configuração a cada chamada
     * - Centralização: todas as configurações em um lugar
     * - Reutilização: mesmo cliente para múltiplas chamadas
     */
    public function __construct()
    {
        $this->httpClient = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('openai.api_key'),
            'Content-Type' => 'application/json',
        ])
        ->baseUrl(config('openai.base_url'))
        ->timeout(config('openai.timeout'));
    }

    /**
     * Envia pergunta para IA e processa resposta em streaming
     *
     * @param string $prompt Pergunta/prompt do usuário
     * @param callable $onData Callback executado para cada "chunk" recebido
     * @return string Resposta completa da IA
     *
     * Como funciona o streaming:
     * 1. Envia requisição para API com stream=true
     * 2. API retorna dados no formato SSE (Server-Sent Events)
     * 3. Cada linha "data: {...}" contém um pedaço da resposta
     * 4. Processamos linha por linha em tempo real
     * 5. Cada pedaço é enviado via callback para o Livewire
     */
    public function streamResponse(string $prompt, callable $onData)
    {
        // Armazena a resposta completa conforme vai chegando
        $fullResponse = '';

        try {
            // Faz requisição POST para API com streaming ativado
            $response = $this->httpClient
            ->withOptions(['stream' => true]) // Ativa streaming HTTP
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

            // Obtém o corpo da resposta como stream PSR-7
            $body = $response->toPsrResponse()->getBody();
            $buffer = ''; // Buffer para acumular dados parciais

            // Loop principal: lê stream até acabar
            while (!$body->eof()) {
                // Lê pedaço pequeno (1KB) para processamento em tempo real
                $chunk = $body->read(1024);
                $buffer .= $chunk;

                // Processa linhas completas (delimitadas por \n)
                while (($pos = strpos($buffer, "\n")) !== false) {
                    // Extrai uma linha completa
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    $line = trim($line);
                    if (empty($line)) continue;

                    // Processa linhas no formato Server-Sent Events
                    if (str_starts_with($line, "data: ")) {
                        // Remove o prefixo "data: "
                        $jsonData = substr($line, 6);

                        // Sinal de fim do streaming
                        if ($jsonData === '[DONE]') {
                            break 2; // Sai dos dois loops
                        }

                        try {
                            // Decodifica JSON da resposta
                            $data = json_decode($jsonData, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                // Extrai o conteúdo do chunk
                                $delta = $data['choices'][0]['delta']['content'] ?? '';
                                if ($delta) {
                                    // Acumula na resposta final
                                    $fullResponse .= $delta;
                                    // Envia chunk para o callback (Livewire)
                                    $onData($delta);
                                }
                            }
                        } catch (\Exception $e) {
                            // Log erro de JSON mas continua processando
                            Log::error('Erro ao processar chunk JSON: ' . $e->getMessage());
                            continue;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            // Em caso de erro geral, loga e notifica
            Log::error('Erro no streaming: ' . $e->getMessage());
            $errorMessage = 'Erro ao processar resposta da API.';
            $onData($errorMessage);
            return $errorMessage;
        }
    }
}
