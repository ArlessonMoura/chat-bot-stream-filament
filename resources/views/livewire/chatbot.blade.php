<div class="space-y-4">
    <!-- Chat Box -->
    <div id="chat-box" class="p-4 bg-gray-800 text-white rounded h-96 overflow-y-auto border border-gray-700">
        @foreach($chat as $msg)
            <div class="{{ $msg['role'] === 'user' ? 'text-blue-400' : 'text-green-400' }} mb-2">
                <strong>{{ $msg['role'] === 'user' ? 'Você' : 'Bot' }}:</strong> {!! $msg['content'] !!}
            </div>
        @endforeach
    </div>

    <!-- Input + Botão -->
    <div class="flex gap-2">
        <input type="text" class="flex-1 rounded px-2 py-1 text-black" placeholder="Digite sua pergunta..."
               wire:model.defer="message"
               wire:keydown.enter="send">
        <button class="bg-primary-600 px-4 py-2 rounded text-white" wire:click="send">Enviar</button>
    </div>

    <!-- Scroll automático -->
    <script>
        Livewire.on('scrollToBottom', () => {
            const box = document.getElementById('chat-box');
            box.scrollTop = box.scrollHeight;
        });
    </script>
</div>
