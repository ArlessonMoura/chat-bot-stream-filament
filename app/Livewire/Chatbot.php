<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AIChatService;
use Illuminate\Support\Facades\Log;

/**
 * Componente Livewire para Chat com IA usando streaming em tempo real
 * 
 * Este componente implementa um chat que funciona como o ChatGPT:
 * - Mantém histórico completo da conversa
 * - Exibe respostas da IA palavra por palavra (streaming)
 * - Interface reativa que atualiza em tempo real
 */
class Chatbot extends Component
{
    // Propriedades públicas do Livewire (sincronizadas com frontend)
    
    /** @var string Texto digitado pelo usuário no campo de input */
    public $prompt = '';
    
    /** @var array Histórico completo da conversa (array de mensagens) */
    public $chat = [];
    
    /** @var bool Indica se o bot está processando uma resposta */
    public $isTyping = false;

    /**
     * Processa o envio de uma nova mensagem pelo usuário
     * 
     * Fluxo:
     * 1. Valida se há texto
     * 2. Adiciona mensagem do usuário ao chat
     * 3. Cria espaço vazio para resposta do bot
     * 4. Chama o método ask() via JavaScript
     */
    public function submitPrompt()
    {
        // Remove espaços em branco e valida se há conteúdo
        $trimmedPrompt = trim($this->prompt);
        if (!$trimmedPrompt) return;

        // Adiciona a mensagem do usuário ao array de chat
        $this->chat[] = ['role' => 'user', 'content' => $trimmedPrompt];
        
        // Limpa o campo de input
        $this->prompt = '';

        // Cria uma mensagem vazia do bot que será preenchida via streaming
        $this->chat[] = ['role' => 'bot', 'content' => ''];

        // Chama o método ask() via JavaScript para iniciar o streaming
        // Isso é necessário para manter a interface responsiva
        $this->js('$wire.ask()');
    }

    /**
     * Processa a resposta da IA usando streaming em tempo real
     * 
     * @param AIChatService $ai Serviço injetado pelo Laravel para comunicar com a API
     * 
     * Fluxo:
     * 1. Marca como "digitando" para mostrar indicador
     * 2. Pega a última pergunta do usuário
     * 3. Chama a API com callback de streaming
     * 4. Cada "chunk" recebido é enviado imediatamente para o frontend
     * 5. Para de "digitar" quando termina
     */
    public function ask(AIChatService $ai)
    {
        // Ativa indicador de "processando resposta..."
        $this->isTyping = true;

        // Busca a última mensagem do usuário no histórico
        $userMessage = collect($this->chat)->where('role', 'user')->last()['content'];
        
        // Índice da mensagem do bot que será preenchida
        $botIndex = count($this->chat) - 1;

        try {
            // Chama o serviço de IA com callback de streaming
            $ai->streamResponse($userMessage, function ($chunk) use ($botIndex) {
                // Adiciona o chunk ao conteúdo da mensagem do bot
                $this->chat[$botIndex]['content'] .= $chunk;
                
                // Envia o chunk para o frontend via wire:stream
                // Isso faz o texto aparecer palavra por palavra na tela
                $this->stream(to: 'bot-message-' . $botIndex, content: $chunk);
            });

        } catch (\Exception $e) {
            // Em caso de erro, registra no log e exibe mensagem de erro
            Log::error('Erro no streaming: ' . $e->getMessage());
            $this->chat[$botIndex]['content'] = 'Erro ao processar sua pergunta. Tente novamente.';
        }

        // Desativa indicador de "processando"
        $this->isTyping = false;
    }

    /**
     * Método obrigatório do Livewire para renderizar a view
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.chatbot');
    }
}
