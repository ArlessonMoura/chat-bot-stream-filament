<?php

namespace App\Http\Controllers;

use App\Services\AIChatService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function stream(AIChatService $ai)
    {
        $prompt = request('message');

        return new StreamedResponse(function () use ($ai, $prompt) {
            $ai->streamResponse($prompt, function ($chunk) {
                echo "data: " . $chunk . "\n\n";
                ob_flush();
                flush();
            });
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
