<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AIChatService;
use Illuminate\Support\Facades\Log;

class Chatbot extends Component
{
    public $prompt = '';
    public $chat = [];
    public $isTyping = false;

    public function submitPrompt()
    {
        $trimmedPrompt = trim($this->prompt);
        if (!$trimmedPrompt) return;

        $this->chat[] = ['role' => 'user', 'content' => $trimmedPrompt];
        $this->prompt = '';

        $this->chat[] = ['role' => 'bot', 'content' => ''];

        $this->js('$wire.ask()');
    }

    public function ask(AIChatService $ai)
    {
        $this->isTyping = true;

        $userMessage = collect($this->chat)->where('role', 'user')->last()['content'];
        $botIndex = count($this->chat) - 1;

        try {
            $ai->streamResponse($userMessage, function ($chunk) use ($botIndex) {
                $this->chat[$botIndex]['content'] .= $chunk;
                $this->stream(to: 'bot-message-' . $botIndex, content: $chunk);
            });

        } catch (\Exception $e) {
            Log::error('Erro no streaming: ' . $e->getMessage());
            $this->chat[$botIndex]['content'] = 'Erro ao processar sua pergunta. Tente novamente.';
        }

        $this->isTyping = false;
    }

    public function render()
    {
        return view('livewire.chatbot');
    }
}
