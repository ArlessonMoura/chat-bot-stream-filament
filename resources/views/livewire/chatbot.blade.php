<div class="space-y-4">
    <!-- Chat Box -->
    <div id="chat-box" class="p-4 bg-gray-800 text-white rounded h-96 overflow-y-auto border border-gray-700">
        @foreach($chat as $index => $message)
            @if($message['role'] === 'user')
                <div class="text-blue-400 mb-2">
                    <strong>Você:</strong> {{ $message['content'] }}
                </div>
            @else
                <div class="text-green-400 mb-2">
                    <strong>Bot:</strong>
                    <span wire:stream="bot-message-{{ $index }}">{{ $message['content'] }}</span>
                    @if(empty($message['content']))
                        <span class="text-gray-400">Processando a resposta...</span>
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    <!-- Input + Botão -->
    <div class="flex gap-2">
        <input type="text" class="flex-1 rounded px-2 py-1 text-black" placeholder="Digite sua pergunta..."
               wire:model="prompt"
               wire:keydown.enter="submitPrompt"
               @disabled($isTyping)>
        <button class="bg-primary-600 px-4 py-2 rounded text-white"
                wire:click="submitPrompt"
                @disabled($isTyping)>
            @if($isTyping)
                Enviando...
            @else
                Enviar
            @endif
        </button>
    </div>

    <!-- Scroll automático -->
    <script>
        document.addEventListener('livewire:updated', () => {
            const box = document.getElementById('chat-box');
            if (box) {
                box.scrollTop = box.scrollHeight;
            }
        });
    </script>
</div>
