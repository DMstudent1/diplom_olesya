<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;

class AiСonsultant extends Controller
{
    protected $chatService;
    
    public function __construct(DeepSeekService $chatService)
    {
        $this->chatService = $chatService;
    }
    
    /**
     * Отправка сообщения и получение ответа консультанта
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
            'session_id' => 'nullable|string'
        ]);
        
        $response = $this->chatService->sendMessage(
            $request->input('message'),
            $request->input('history', []),
            $request->input('session_id')
        );
        
        return response()->json($response);
    }
    
    /**
     * Потоковый ответ (для live-чата)
     */
    public function streamMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array'
        ]);
        
        return response()->stream(function () use ($request) {
            $this->chatService->streamMessage(
                $request->input('message'),
                $request->input('history', [])
            );
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no'
        ]);
    }
}