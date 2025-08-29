<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AIChatService;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class Chatbot extends Component
{
   public $message = '';
    public $chat = [];

    public function send(AIChatService $ai)
    {
        $userMessage = trim($this->message);
        if (!$userMessage) return;

        // Adiciona a mensagem do usuário
        $this->chat[] = ['role' => 'user', 'content' => $userMessage];
        $this->message = '';

        // Cria resposta inicial vazia
        $this->chat[] = ['role' => 'bot', 'content' => ''];
        $botIndex = count($this->chat) - 1;

        // Streaming chunk por chunk
        
        $teste = $ai->streamResponse($userMessage, function ($chunk) use (&$botIndex) {      
                      
            $this->chat[$botIndex]['content'] .= $chunk;
            $this->emit('scrollToBottom');
        });
        Log::info($teste);
    }

    public function render()
    {
        return view('livewire.chatbot');
    }
}
